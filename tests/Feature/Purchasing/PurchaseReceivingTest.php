<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Purchasing\Services\PurchaseReceivingService;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_partial_and_full_receive_updates_balance_and_movements(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');

        $poService = app(PurchaseOrderService::class);
        $receivingService = app(PurchaseReceivingService::class);

        $po = $poService->create($user, $store, $supplier, [
            'order_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'ordered_qty' => 10, 'unit_cost' => 20],
        ]);
        $poService->approve($po, $user);
        $line = $po->fresh('lines')->lines->first();

        $receivingService->receive($po->fresh('lines'), $user, [
            ['line_id' => $line->id, 'receive_qty' => 4],
        ]);

        $inventory = StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertEquals(9, (float) $inventory->qty);
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'purchase_receipt')->count());

        $po->refresh();
        $this->assertSame('partially_received', $po->status);

        $receivingService->receive($po->fresh('lines'), $user, [
            ['line_id' => $line->id, 'receive_qty' => 6],
        ]);

        $inventory->refresh();
        $this->assertEquals(15, (float) $inventory->qty);
        $this->assertSame(2, StockMovement::query()->where('movement_type', 'purchase_receipt')->count());
        $this->assertSame('received', $po->fresh()->status);
    }
}
