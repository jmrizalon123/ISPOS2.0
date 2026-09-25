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

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_inventory_low_stock_detection(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $ok = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'warning_qty' => 10,
            'status' => 'active',
        ]);

        $low = Product::factory()->create([
            'company_id' => $company->id,
            'sku' => 'LOW-001',
            'track_inventory' => true,
            'warning_qty' => 10,
            'status' => 'active',
        ]);

        StoreProductInventory::create(['company_id' => $company->id, 'store_id' => $store->id, 'product_id' => $ok->id, 'qty' => 50]);
        StoreProductInventory::create(['company_id' => $company->id, 'store_id' => $store->id, 'product_id' => $low->id, 'qty' => 8]);

        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->get(route('admin.inventory.low-stock', ['store_id' => $store->id]))
            ->assertOk();
    }

    public function test_inventory_clerk_can_view_low_stock_page(): void
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
            'qty' => 2,
        ]);

        $clerk = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$store->id]);

        $this->actingAs($clerk)
            ->get(route('admin.inventory.low-stock'))
            ->assertOk();
    }

    public function test_company_admin_can_save_product_with_inventory_thresholds(): void
    {
        $company = Company::factory()->create();
        Store::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sku' => 'STOCK-001',
            'name' => 'Stocked Item',
            'description' => null,
            'product_type' => 'retail',
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 10,
            'base_price' => 25,
            'track_inventory' => true,
            'ideal_qty' => 50,
            'warning_qty' => 12,
            'has_variants' => false,
            'has_modifiers' => false,
            'has_components' => false,
            'image' => null,
            'status' => 'active',
            'variants' => [],
            'barcodes' => [],
            'prices' => [],
            'modifier_groups' => [],
            'components' => [],
            'ingredients' => [],
            'images' => [],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'STOCK-001')->firstOrFail();
        $this->assertSame('50.0000', (string) $product->ideal_qty);
        $this->assertSame('12.0000', (string) $product->warning_qty);
        $this->assertSame(1, StoreProductInventory::query()->where('product_id', $product->id)->count());
    }
}
