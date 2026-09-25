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

class StoreInventoryInitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_new_tracked_product_creates_zero_rows_at_all_stores(): void
    {
        $company = Company::factory()->create();
        Store::factory()->count(2)->create(['company_id' => $company->id, 'status' => 'active']);

        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'company_id' => $company->id,
                'sku' => 'NEW-STOCK',
                'name' => 'New Stock Item',
                'product_type' => 'retail',
                'cost' => 10,
                'base_price' => 25,
                'track_inventory' => true,
                'ideal_qty' => 50,
                'warning_qty' => 10,
                'has_variants' => false,
                'has_modifiers' => false,
                'has_components' => false,
                'status' => 'active',
                'variants' => [],
                'barcodes' => [],
                'prices' => [],
                'modifier_groups' => [],
                'components' => [],
                'ingredients' => [],
                'images' => [],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'NEW-STOCK')->firstOrFail();
        $this->assertSame(2, StoreProductInventory::query()->where('product_id', $product->id)->count());
        $this->assertEquals(0, (float) StoreProductInventory::query()->where('product_id', $product->id)->sum('qty'));
    }
}
