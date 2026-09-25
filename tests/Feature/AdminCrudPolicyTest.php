<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_manage_companies(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get(route('admin.companies.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.companies.create'))
            ->assertForbidden();
    }

    public function test_developer_can_create_store(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Developer');

        $this->actingAs($user)
            ->post(route('admin.stores.store'), [
                'company_id' => $company->id,
                'store_code' => 'DEV',
                'store_name' => 'Developer Store',
                'currency' => 'PHP',
                'timezone' => 'Asia/Manila',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stores', [
            'company_id' => $company->id,
            'store_code' => 'DEV',
        ]);
    }

    public function test_cashier_cannot_access_company_admin(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.companies.index'))
            ->assertForbidden();
    }

    public function test_company_admin_can_view_stores_but_not_create(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Company Admin');

        $this->actingAs($user)
            ->get(route('admin.stores.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.stores.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.stores.store'), [
                'company_id' => $company->id,
                'store_code' => 'NEW',
                'store_name' => 'New Store',
                'currency' => 'PHP',
                'timezone' => 'Asia/Manila',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_company_admin_can_assign_roles_to_users(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $store = Store::create([
            'company_id' => $company->id,
            'store_code' => 'S1',
            'store_name' => 'Store 1',
            'currency' => 'PHP',
            'timezone' => 'Asia/Manila',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $staff = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'company_id' => $company->id,
                'default_store_id' => $store->id,
                'base_type' => 'store',
                'status' => 'active',
                'password' => '',
                'password_confirmation' => '',
                'roles' => ['Cashier'],
                'store_ids' => [$store->id],
            ])
            ->assertRedirect();

        $staff->refresh();
        $this->assertTrue($staff->hasRole('Cashier'));
        $this->assertTrue($staff->stores->contains('id', $store->id));
    }
}
