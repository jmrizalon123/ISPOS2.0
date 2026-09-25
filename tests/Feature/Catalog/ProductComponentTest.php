<?php

namespace Tests\Feature\Catalog;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_create_menu_item_with_product_components(): void
    {
        $company = Company::create([
            'company_code' => 'CCMP',
            'name' => 'Component Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $burger = Product::factory()->create([
            'company_id' => $company->id,
            'sku' => 'ITEM-BURGER',
            'name' => 'Standalone Burger',
            'product_type' => 'menu_item',
        ]);

        $drink = Product::factory()->create([
            'company_id' => $company->id,
            'sku' => 'ITEM-DRINK',
            'name' => 'Soft Drink',
            'product_type' => 'retail',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sku' => 'COMBO-001',
            'name' => 'Burger Combo',
            'description' => 'Meal deal',
            'product_type' => 'menu_item',
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 100,
            'base_price' => 249,
            'track_inventory' => false,
            'has_variants' => false,
            'has_modifiers' => false,
            'has_components' => true,
            'status' => 'active',
            'variants' => [],
            'barcodes' => [],
            'prices' => [],
            'modifier_groups' => [],
            'components' => [
                [
                    'component_product_id' => $burger->id,
                    'quantity' => 1,
                    'unit_id' => null,
                    'is_optional' => false,
                    'sort_order' => 0,
                    'notes' => 'Main item',
                ],
                [
                    'component_product_id' => $drink->id,
                    'quantity' => 1,
                    'unit_id' => null,
                    'is_optional' => false,
                    'sort_order' => 1,
                    'notes' => null,
                ],
            ],
            'ingredients' => [],
            'images' => [],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'COMBO-001')->firstOrFail();
        $this->assertTrue($product->has_components);
        $this->assertSame(2, ProductComponent::query()->where('product_id', $product->id)->count());
    }
}
