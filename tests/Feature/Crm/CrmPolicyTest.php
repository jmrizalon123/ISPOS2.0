<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_inventory_clerk_cannot_access_customers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Inventory Clerk');

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_cashier_can_access_customers_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_cashier_cannot_create_promotions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.promotions.create'))
            ->assertForbidden();
    }
}
