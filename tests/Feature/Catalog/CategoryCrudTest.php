<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_create_and_list_categories(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
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
            ->post(route('admin.categories.store'), [
                'company_id' => $company->id,
                'category_code' => 'SNACKS',
                'name' => 'Snacks',
                'description' => 'Snack items',
                'sort_order' => 10,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'company_id' => $company->id,
            'category_code' => 'SNACKS',
            'name' => 'Snacks',
        ]);

        $category = Category::query()->where('category_code', 'SNACKS')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk();
    }

    public function test_inventory_clerk_can_create_brand(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $clerk = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $clerk->assignRole('Inventory Clerk');

        $this->actingAs($clerk)
            ->post(route('admin.brands.store'), [
                'company_id' => $company->id,
                'brand_code' => 'LOCAL',
                'name' => 'Local Brand',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', [
            'company_id' => $company->id,
            'brand_code' => 'LOCAL',
        ]);
    }
}
