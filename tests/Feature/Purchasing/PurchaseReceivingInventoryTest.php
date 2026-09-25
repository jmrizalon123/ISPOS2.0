<?php

namespace Tests\Feature\Purchasing;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceivingInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_po_rejects_untracked_products(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $untracked = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => false,
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
                'lines' => [
                    ['product_id' => $untracked->id, 'ordered_qty' => 10, 'unit_cost' => 5],
                ],
            ])
            ->assertSessionHasErrors('lines.0.product_id');
    }
}
