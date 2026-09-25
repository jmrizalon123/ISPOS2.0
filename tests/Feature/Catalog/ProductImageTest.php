<?php

namespace Tests\Feature\Catalog;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_company_admin_can_upload_multiple_images_and_set_default(): void
    {
        $company = Company::create([
            'company_code' => 'CIMG',
            'name' => 'Image Co',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sku' => 'IMG-001',
            'name' => 'Product With Images',
            'description' => null,
            'product_type' => 'retail',
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'cost' => 10,
            'base_price' => 25,
            'track_inventory' => true,
            'qty' => 5,
            'ideal_qty' => 10,
            'warning_qty' => 2,
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
            'images' => [
                [
                    'client_key' => 'img-1',
                    'upload_index' => 0,
                    'is_default' => false,
                    'sort_order' => 0,
                ],
                [
                    'client_key' => 'img-2',
                    'upload_index' => 1,
                    'is_default' => true,
                    'sort_order' => 1,
                ],
            ],
            'image_uploads' => [
                UploadedFile::fake()->image('one.jpg')->size(400),
                UploadedFile::fake()->image('two.jpg')->size(500),
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'IMG-001')->firstOrFail();
        $this->assertSame(2, ProductImage::query()->where('product_id', $product->id)->count());

        $default = ProductImage::query()->where('product_id', $product->id)->where('is_default', true)->firstOrFail();
        $this->assertSame('two.jpg', $default->original_name);
        $this->assertNotNull($product->fresh()->image);
        $this->assertSame('/storage/'.$default->path, $default->url);
        Storage::disk('public')->assertExists($default->path);
    }

    public function test_image_upload_rejects_files_over_one_megabyte(): void
    {
        $company = Company::create([
            'company_code' => 'CIMG2',
            'name' => 'Image Co 2',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $payload = [
            'company_id' => $company->id,
            'sku' => 'IMG-002',
            'name' => 'Oversized Image Product',
            'product_type' => 'retail',
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
            'images' => [
                [
                    'client_key' => 'img-1',
                    'upload_index' => 0,
                    'is_default' => true,
                    'sort_order' => 0,
                ],
            ],
            'image_uploads' => [
                UploadedFile::fake()->create('large.jpg', 1025, 'image/jpeg'),
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors('image_uploads.0');
    }
}
