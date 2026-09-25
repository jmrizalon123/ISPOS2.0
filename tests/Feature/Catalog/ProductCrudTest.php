<?php

namespace Tests\Feature\Catalog;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Category;
use App\Models\Company;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\SalesPlan;
use App\Models\ProductBarcode;
use App\Models\ProductIngredient;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_create_product_with_variant_barcode_and_price(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $priceGroup = PriceGroup::factory()->create(['company_id' => $company->id, 'is_default' => true]);
        $salesPlan = SalesPlan::factory()->create(['company_id' => $company->id, 'plan_code' => 'RETAIL']);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
            'description' => 'Sample product',
            'product_type' => 'retail',
            'category_id' => $category->id,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 50,
            'base_price' => 99,
            'track_inventory' => true,
            'qty' => 100,
            'ideal_qty' => 50,
            'warning_qty' => 10,
            'has_variants' => true,
            'has_modifiers' => false,
            'has_components' => false,
            'image' => null,
            'status' => 'active',
            'variants' => [
                [
                    'client_key' => 'v1',
                    'variant_code' => 'SM',
                    'name' => 'Small',
                    'sku' => 'TEST-SKU-001-SM',
                    'cost' => 50,
                    'selling_price' => 99,
                    'sort_order' => 0,
                    'status' => 'active',
                ],
            ],
            'barcodes' => [
                [
                    'barcode' => '1234567890123',
                    'is_primary' => true,
                    'variant_client_key' => 'v1',
                ],
            ],
            'prices' => [
                [
                    'price_group_id' => $priceGroup->id,
                    'price' => 99,
                    'variant_client_key' => 'v1',
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'TEST-SKU-001')->firstOrFail();
        $this->assertSame('Test Product', $product->name);
        $this->assertSame($salesPlan->id, $product->sales_plan_id);
        $this->assertTrue($product->has_variants);

        $variant = ProductVariant::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame('SM', $variant->variant_code);

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'barcode' => '1234567890123',
        ]);

        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'price_group_id' => $priceGroup->id,
        ]);

        $this->assertSame(1, ProductBarcode::query()->where('product_id', $product->id)->count());
        $this->assertSame(1, ProductPrice::query()->where('product_id', $product->id)->count());
    }

    public function test_company_admin_can_update_product_via_put(): void
    {
        $company = Company::create([
            'company_code' => 'CUPD',
            'name' => 'Update Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $salesPlan = SalesPlan::factory()->create(['company_id' => $company->id, 'plan_code' => 'RETAIL']);

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'UPD-001',
            'name' => 'Before Update',
            'product_type' => 'retail',
            'status' => 'active',
        ]);

        $payload = [
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'UPD-001',
            'name' => 'After Update',
            'description' => 'Updated description',
            'product_type' => 'retail',
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 10,
            'base_price' => 25,
            'track_inventory' => false,
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
        ];

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame('After Update', $product->fresh()->name);
    }

    public function test_company_admin_can_create_menu_item_with_modifiers_and_ingredients(): void
    {
        $company = Company::create([
            'company_code' => 'C2',
            'name' => 'Restaurant Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $priceGroup = PriceGroup::factory()->create(['company_id' => $company->id, 'is_default' => true]);
        $salesPlan = SalesPlan::factory()->create(['company_id' => $company->id, 'plan_code' => 'RESTO']);

        $bun = Product::factory()->create([
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'ING-BUN',
            'name' => 'Burger Bun',
            'product_type' => 'ingredient',
        ]);
        $patty = Product::factory()->create([
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'ING-PATTY',
            'name' => 'Beef Patty',
            'product_type' => 'ingredient',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'sku' => 'BURGER-001',
            'name' => 'House Burger',
            'description' => 'Signature burger',
            'product_type' => 'menu_item',
            'category_id' => $category->id,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 80,
            'base_price' => 199,
            'track_inventory' => false,
            'has_variants' => false,
            'has_modifiers' => true,
            'has_components' => false,
            'image' => null,
            'status' => 'active',
            'variants' => [],
            'barcodes' => [],
            'prices' => [
                [
                    'price_group_id' => $priceGroup->id,
                    'price' => 199,
                    'variant_client_key' => '',
                ],
            ],
            'components' => [],
            'modifier_groups' => [
                [
                    'client_key' => 'g1',
                    'group_code' => 'SIZE',
                    'name' => 'Size',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'min_selections' => 1,
                    'max_selections' => 1,
                    'sort_order' => 0,
                    'status' => 'active',
                    'options' => [
                        [
                            'client_key' => 'o1',
                            'option_code' => 'REG',
                            'name' => 'Regular',
                            'price_adjustment' => 0,
                            'is_default' => true,
                            'sort_order' => 0,
                            'status' => 'active',
                        ],
                        [
                            'client_key' => 'o2',
                            'option_code' => 'DBL',
                            'name' => 'Double',
                            'price_adjustment' => 50,
                            'is_default' => false,
                            'sort_order' => 1,
                            'status' => 'active',
                        ],
                    ],
                ],
            ],
            'ingredients' => [
                [
                    'ingredient_product_id' => $bun->id,
                    'quantity' => 1,
                    'unit_id' => null,
                    'is_optional' => false,
                    'sort_order' => 0,
                    'notes' => null,
                ],
                [
                    'ingredient_product_id' => $patty->id,
                    'quantity' => 1,
                    'unit_id' => null,
                    'is_optional' => false,
                    'sort_order' => 1,
                    'notes' => null,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'BURGER-001')->firstOrFail();
        $this->assertSame('menu_item', $product->product_type);
        $this->assertTrue($product->has_modifiers);

        $group = ProductModifierGroup::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame('SIZE', $group->group_code);
        $this->assertSame(2, ProductModifierOption::query()->where('product_modifier_group_id', $group->id)->count());

        $this->assertSame(2, ProductIngredient::query()->where('product_id', $product->id)->count());
    }

    public function test_single_create_page_still_uses_product_form(): void
    {
        $company = Company::create([
            'company_code' => 'CSGL',
            'name' => 'Single Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->withSession([
                BackofficeContextService::SESSION_KEY => [
                    'scope' => 'company',
                    'company_id' => $company->id,
                    'store_id' => null,
                ],
                BackofficeContextService::ESTABLISHED_SESSION_KEY => true,
            ])
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Form')
                ->where('product', null));
    }

    public function test_bulk_create_page_renders_bulk_form(): void
    {
        $company = Company::create([
            'company_code' => 'CBLK',
            'name' => 'Bulk Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->withSession([
                BackofficeContextService::SESSION_KEY => [
                    'scope' => 'company',
                    'company_id' => $company->id,
                    'store_id' => null,
                ],
                BackofficeContextService::ESTABLISHED_SESSION_KEY => true,
            ])
            ->get(route('admin.products.bulk-create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/BulkCreate')
                ->has('companies')
                ->has('salesPlans'));
    }

    public function test_company_admin_can_create_multiple_products_in_one_save(): void
    {
        $company = Company::create([
            'company_code' => 'CMUL',
            'name' => 'Multi Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $salesPlan = SalesPlan::factory()->create(['company_id' => $company->id, 'plan_code' => 'RETAIL']);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sales_plan_id' => $salesPlan->id,
            'product_type' => 'retail',
            'tax_id' => null,
            'track_inventory' => true,
            'status' => 'active',
            'items' => [
                [
                    'sku' => 'BULK-001',
                    'name' => 'Bulk Product One',
                    'description' => 'First row',
                    'cost' => 10,
                    'base_price' => 20,
                    'barcode' => '1111111111111',
                    'ideal_qty' => 50,
                    'warning_qty' => 5,
                ],
                [
                    'sku' => 'BULK-002',
                    'name' => 'Bulk Product Two',
                    'description' => null,
                    'cost' => 15,
                    'base_price' => 30,
                    'barcode' => '2222222222222',
                    'ideal_qty' => null,
                    'warning_qty' => null,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $first = Product::query()->where('sku', 'BULK-001')->firstOrFail();
        $second = Product::query()->where('sku', 'BULK-002')->firstOrFail();

        $this->assertSame('Bulk Product One', $first->name);
        $this->assertSame($salesPlan->id, $first->sales_plan_id);
        $this->assertNull($first->category_id);
        $this->assertSame($salesPlan->id, $second->sales_plan_id);
        $this->assertSame('retail', $second->product_type);

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $first->id,
            'barcode' => '1111111111111',
        ]);
        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $second->id,
            'barcode' => '2222222222222',
        ]);
    }

    public function test_bulk_create_rejects_duplicate_skus_in_the_same_request(): void
    {
        $company = Company::create([
            'company_code' => 'CDUP',
            'name' => 'Dupe Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $salesPlan = SalesPlan::factory()->create(['company_id' => $company->id, 'plan_code' => 'RETAIL']);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-store'), [
                'company_id' => $company->id,
                'sales_plan_id' => $salesPlan->id,
                'product_type' => 'retail',
                'track_inventory' => true,
                'status' => 'active',
                'items' => [
                    ['sku' => 'DUP-001', 'name' => 'First'],
                    ['sku' => 'DUP-001', 'name' => 'Second'],
                ],
            ])
            ->assertSessionHasErrors(['items.0.sku', 'items.1.sku']);

        $this->assertSame(0, Product::query()->where('sku', 'DUP-001')->count());
    }
}
