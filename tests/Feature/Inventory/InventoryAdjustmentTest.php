<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manual_adjustment_updates_balance_and_creates_movement(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
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

        $clerk = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$store->id]);

        $this->actingAs($clerk)
            ->post(route('admin.inventory.adjustments.store'), [
                'store_id' => $store->id,
                'product_id' => $product->id,
                'quantity_delta' => 5,
                'reason' => 'Stock count correction',
            ])
            ->assertRedirect();

        $this->assertSame('15.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->assertSame(1, StockMovement::query()->where('movement_type', 'adjustment')->count());
    }

    public function test_cashier_cannot_adjust_inventory(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

        $cashier = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier)
            ->post(route('admin.inventory.adjustments.store'), [
                'store_id' => $store->id,
                'product_id' => $product->id,
                'quantity_delta' => 5,
                'reason' => 'Test',
            ])
            ->assertForbidden();
    }
}
