<?php

namespace Tests\Feature\Purchasing;

use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_draft_create_and_approve_blocks_edit(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');
        $user->stores()->sync([$store->id]);

        $this->actingAs($user)
            ->post(route('admin.purchase-orders.store'), [
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'expected_date' => null,
                'notes' => null,
                'lines' => [
                    ['product_id' => $product->id, 'ordered_qty' => 10, 'unit_cost' => 25, 'notes' => null],
                ],
            ])
            ->assertRedirect();

        $po = PurchaseOrder::query()->firstOrFail();
        $this->assertSame('draft', $po->status);

        $this->actingAs($user)
            ->post(route('admin.purchase-orders.approve', $po))
            ->assertRedirect();

        $po->refresh();
        $this->assertSame('approved', $po->status);

        $this->actingAs($user)
            ->put(route('admin.purchase-orders.update', $po), [
                'order_date' => now()->toDateString(),
                'lines' => [
                    ['product_id' => $product->id, 'ordered_qty' => 20, 'unit_cost' => 25, 'notes' => null],
                ],
            ])
            ->assertForbidden();
    }
}
