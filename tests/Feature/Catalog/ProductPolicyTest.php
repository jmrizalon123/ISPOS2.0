<?php

namespace Tests\Feature\Catalog;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_manage_products_within_company(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $otherCompany = Company::create([
            'company_code' => 'C2',
            'name' => 'Company 2',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['company_id' => $company->id]);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $otherProduct))
            ->assertForbidden();
    }

    public function test_cashier_cannot_create_products(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $cashier = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier)
            ->get(route('admin.products.create'))
            ->assertForbidden();
    }
}
