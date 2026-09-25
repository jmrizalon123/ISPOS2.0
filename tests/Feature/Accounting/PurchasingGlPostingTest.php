<?php

namespace Tests\Feature\Accounting;

use App\Domains\Accounting\Services\DefaultChartOfAccountsService;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Purchasing\Services\PurchaseReceivingService;
use App\Domains\Purchasing\Services\PurchaseReturnService;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingGlPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    /** @return array{company: Company, store: Store, supplier: Supplier, user: User} */
    protected function purchasingFixtures(): array
    {
        $company = Company::factory()->create(['enable_accounting' => true]);
        $store = Store::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');

        app(DefaultChartOfAccountsService::class)->seedForCompany($company, $user);

        return compact('company', 'store', 'supplier', 'user');
    }

    public function test_receive_creates_receipt_vendor_bill_and_gl_entry(): void
    {
        extract($this->purchasingFixtures());

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 0,
        ]);

        $poService = app(PurchaseOrderService::class);
        $receivingService = app(PurchaseReceivingService::class);

        $po = $poService->create($user, $store, $supplier, [
            'order_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'ordered_qty' => 5, 'unit_cost' => 20],
        ]);
        $poService->approve($po, $user);
        $line = $po->fresh('lines')->lines->first();

        $receivingService->receive($po->fresh('lines'), $user, [
            ['line_id' => $line->id, 'receive_qty' => 5],
        ]);

        $receipt = PurchaseReceipt::query()->where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($receipt);
        $this->assertSame('100.0000', (string) $receipt->total_amount);

        $this->assertDatabaseHas('vendor_bills', [
            'purchase_receipt_id' => $receipt->id,
            'amount_due' => 100,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'source_type' => PurchaseReceipt::class,
            'source_id' => $receipt->id,
            'status' => 'posted',
        ]);
    }

    public function test_purchase_return_post_creates_ap_reversal_gl_entry(): void
    {
        extract($this->purchasingFixtures());

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $returnService = app(PurchaseReturnService::class);

        $purchaseReturn = $returnService->create($user, $store, $supplier, [
            'reason' => 'Damaged goods',
        ], [
            ['product_id' => $product->id, 'qty' => 2, 'unit_cost' => 25],
        ]);

        $returnService->post($purchaseReturn, $user);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'source_type' => PurchaseReturn::class,
            'source_id' => $purchaseReturn->id,
            'status' => 'posted',
        ]);
    }

    public function test_supplier_payment_updates_bill_and_posts_gl(): void
    {
        extract($this->purchasingFixtures());

        $accountant = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $accountant->assignRole('Accountant');

        $bill = VendorBill::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'amount_due' => 100,
            'amount_paid' => 0,
            'status' => 'open',
        ]);

        $this->actingAs($accountant)
            ->post(route('admin.supplier-payments.store'), [
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'amount' => 100,
                'allocations' => [
                    ['vendor_bill_id' => $bill->id, 'amount' => 100],
                ],
            ])
            ->assertRedirect(route('admin.supplier-payments.index'));

        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertSame('100.0000', (string) $bill->amount_paid);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'source_type' => \App\Models\SupplierPayment::class,
            'status' => 'posted',
        ]);
    }
}
