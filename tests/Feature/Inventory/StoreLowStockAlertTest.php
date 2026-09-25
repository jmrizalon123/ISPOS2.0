<?php

namespace Tests\Feature\Inventory;

use App\Domains\Inventory\Services\LowStockAlertService;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_low_stock_uses_store_inventory_not_product_qty(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'qty' => 100,
            'warning_qty' => 10,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        $service = app(LowStockAlertService::class);
        $this->assertSame(1, $service->count($admin, $company->id, $store->id));
    }
}
