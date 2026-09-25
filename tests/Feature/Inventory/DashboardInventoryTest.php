<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_loads_with_store_scoped_low_stock_stats(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'warning_qty' => 10,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 3,
        ]);

        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
