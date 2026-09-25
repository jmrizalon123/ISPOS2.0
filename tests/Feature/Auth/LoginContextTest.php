<?php

namespace Tests\Feature\Auth;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_employee_with_one_store_is_auto_scoped_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'cashier@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        $store = Store::factory()->create(['company_id' => $user->company_id]);
        $user->stores()->attach($store);
        $user->assignRole('Cashier');

        $this->post('/login', [
            'email' => 'cashier@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.context.scope', 'store')
                ->where('auth.context.store_id', $store->id));
    }

    public function test_store_employee_with_multiple_stores_is_prompted_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'multi@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        $stores = Store::factory()->count(2)->create(['company_id' => $user->company_id]);
        $user->stores()->attach($stores);
        $user->assignRole('Cashier');

        $this->post('/login', [
            'email' => 'multi@example.com',
            'password' => 'password',
        ])->assertRedirect(route('login-context.create'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login-context.create'));

        $this->post(route('login-context.store'), [
            'scope' => 'store',
            'store_id' => $stores[1]->id,
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.context.scope', 'store')
                ->where('auth.context.store_id', $stores[1]->id));
    }

    public function test_head_office_user_can_select_company_scope(): void
    {
        $user = User::factory()->headOffice()->create([
            'email' => 'ho@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        Store::factory()->count(2)->create(['company_id' => $user->company_id]);
        $user->assignRole('Company Admin');

        $this->post('/login', [
            'email' => 'ho@example.com',
            'password' => 'password',
        ])->assertRedirect(route('login-context.create'));

        $this->post(route('login-context.store'), [
            'scope' => 'company',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.context.scope', 'company')
                ->where('auth.context.company_id', $user->company_id));
    }

    public function test_store_scope_limits_stores_and_registers_lists(): void
    {
        $user = User::factory()->create([
            'email' => 'scoped@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        $stores = Store::factory()->count(2)->create(['company_id' => $user->company_id]);
        $user->stores()->attach($stores);
        $user->assignRole('Store Manager');

        Register::factory()->create(['store_id' => $stores[0]->id]);
        Register::factory()->create(['store_id' => $stores[1]->id]);

        $this->post('/login', [
            'email' => 'scoped@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login-context.store'), [
            'scope' => 'store',
            'store_id' => $stores[0]->id,
        ]);

        $this->get(route('admin.stores.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stores.data', 1)
                ->where('stores.data.0.id', $stores[0]->id));

        $this->get(route('admin.registers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('registers.data', 1)
                ->where('registers.data.0.store_id', $stores[0]->id));
    }

    public function test_store_scope_limits_admin_users_index(): void
    {
        $admin = User::factory()->headOffice()->create([
            'email' => 'users-admin@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $stores = Store::factory()->count(2)->create(['company_id' => $admin->company_id]);
        $storeUser = User::factory()->create(['company_id' => $admin->company_id, 'status' => 'active']);
        $otherStoreUser = User::factory()->create(['company_id' => $admin->company_id, 'status' => 'active']);
        $storeUser->stores()->attach($stores[0]);
        $otherStoreUser->stores()->attach($stores[1]);

        $this->post('/login', [
            'email' => 'users-admin@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login-context.store'), [
            'scope' => 'store',
            'store_id' => $stores[0]->id,
        ]);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $storeUser->id));

        $this->get(route('admin.users.edit', $storeUser))->assertOk();
        $this->get(route('admin.users.edit', $otherStoreUser))->assertForbidden();
    }

    public function test_lost_context_after_established_session_redirects_to_login(): void
    {
        $user = User::factory()->headOffice()->create([
            'email' => 'expired@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        Store::factory()->count(2)->create(['company_id' => $user->company_id]);
        $user->assignRole('Company Admin');

        $this->post('/login', [
            'email' => 'expired@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login-context.store'), [
            'scope' => 'company',
        ])->assertRedirect(route('dashboard'));

        session()->forget(BackofficeContextService::SESSION_KEY);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_company_admin_with_no_stores_auto_selects_company_scope(): void
    {
        $user = User::factory()->headOffice()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('Company Admin');

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.context.scope', 'company')
                ->where('auth.context.company_id', $user->company_id));
    }
}
