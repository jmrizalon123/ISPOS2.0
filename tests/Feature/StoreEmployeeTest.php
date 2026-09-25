<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SetsBackofficeContext;
use Tests\TestCase;

class StoreEmployeeTest extends TestCase
{
    use RefreshDatabase;
    use SetsBackofficeContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_bulk_assign_store_employees(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $cashier = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->setBackofficeStoreContext($admin, $store->id);

        $this->post(route('admin.users.store-employees.bulk-store'), [
                'store_id' => $store->id,
                'user_ids' => [$cashier->id],
            ])
            ->assertRedirect(route('admin.users.store-employees.index', ['store_id' => $store->id]));

        $cashier->refresh();
        $this->assertTrue($cashier->stores->contains('id', $store->id));
    }

    public function test_store_employees_index_is_accessible(): void
    {
        $company = Company::factory()->create();
        Store::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin);
        $this->setBackofficeCompanyContext($admin);

        $this->get(route('admin.users.store-employees.index'))
            ->assertOk();
    }

    public function test_store_employees_index_includes_audit_and_store_access(): void
    {
        $company = Company::factory()->create();
        $storeA = Store::factory()->create(['company_id' => $company->id, 'store_name' => 'Alpha Store']);
        $storeB = Store::factory()->create(['company_id' => $company->id, 'store_name' => 'Beta Store']);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $employee = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $employee->stores()->attach([$storeA->id, $storeB->id]);

        $this->actingAs($admin);
        $this->setBackofficeCompanyContext($admin);

        $this->get(route('admin.users.store-employees.index', ['store_id' => $storeA->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/StoreEmployees/Index')
                ->has('assignments', 1)
                ->where('assignments.0.user_id', $employee->id)
                ->where('assignments.0.creator.id', $admin->id)
                ->where('assignments.0.updater.id', $admin->id)
                ->has('assignments.0.stores', 2)
                ->where('assignments.0.stores.0.store_name', 'Alpha Store')
                ->where('assignments.0.stores.1.store_name', 'Beta Store')
            );
    }

    public function test_store_scope_shows_only_session_store_on_index(): void
    {
        $company = Company::factory()->create();
        $storeA = Store::factory()->create(['company_id' => $company->id, 'store_name' => 'Alpha Store']);
        $storeB = Store::factory()->create(['company_id' => $company->id, 'store_name' => 'Beta Store']);
        $admin = User::factory()->headOffice()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin);
        $this->setBackofficeStoreContext($admin, $storeA->id);

        $this->get(route('admin.users.store-employees.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('lockStoreSelection', true)
                ->has('stores', 1)
                ->where('stores.0.id', $storeA->id)
                ->where('selectedStoreId', $storeA->id));

        $this->get(route('admin.users.store-employees.index', ['store_id' => $storeB->id]))
            ->assertForbidden();
    }
}
