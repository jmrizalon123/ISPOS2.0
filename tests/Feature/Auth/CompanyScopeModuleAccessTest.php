<?php

namespace Tests\Feature\Auth;

use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SetsBackofficeContext;
use Tests\TestCase;

class CompanyScopeModuleAccessTest extends TestCase
{
    use RefreshDatabase;
    use SetsBackofficeContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_scope_cannot_access_company_only_modules(): void
    {
        $user = User::factory()->headOffice()->create(['status' => 'active']);
        $user->assignRole('Company Admin');

        $store = Store::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user);
        $this->setBackofficeStoreContext($user, $store->id);

        $this->get(route('admin.companies.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.settings.edit'))->assertForbidden();
        $this->get(route('admin.pos-ui.edit'))->assertForbidden();
        $this->get(route('admin.pos-devices.index'))->assertForbidden();
    }

    public function test_store_scope_can_view_registers_but_not_create_or_edit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Store Manager');

        $store = Store::factory()->create(['company_id' => $user->company_id]);
        $user->stores()->sync([$store->id]);

        $register = Register::factory()->create(['store_id' => $store->id]);

        $this->actingAs($user);
        $this->setBackofficeStoreContext($user, $store->id);

        $this->get(route('admin.registers.index'))->assertOk();
        $this->get(route('admin.registers.create'))->assertForbidden();
        $this->get(route('admin.registers.edit', $register))->assertForbidden();
    }

    public function test_company_scope_can_access_company_only_modules(): void
    {
        $user = User::factory()->headOffice()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $this->actingAs($user);
        $this->setBackofficeCompanyContext($user);

        $this->get(route('admin.companies.index'))->assertOk();
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.settings.edit'))->assertOk();
        $this->get(route('admin.pos-ui.edit'))->assertOk();
        $this->get(route('admin.pos-devices.index'))->assertOk();
    }
}
