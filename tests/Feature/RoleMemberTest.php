<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_bulk_assign_role_members(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $cashierRole = Role::query()->where('name', 'Cashier')->firstOrFail();
        $employee = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.role-members.bulk-store'), [
                'role_id' => $cashierRole->id,
                'user_ids' => [$employee->id],
            ])
            ->assertRedirect(route('admin.users.role-members.index', ['role_id' => $cashierRole->id]));

        $employee->refresh();
        $this->assertTrue($employee->hasRole('Cashier'));
    }

    public function test_role_members_index_is_accessible(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->get(route('admin.users.role-members.index'))
            ->assertOk();
    }

    public function test_role_members_index_lists_users_for_selected_role(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $cashierRole = Role::query()->where('name', 'Cashier')->firstOrFail();
        $cashier = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $cashier->assignRole('Cashier');

        $this->actingAs($admin)
            ->get(route('admin.users.role-members.index', ['role_id' => $cashierRole->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/RoleMembers/Index')
                ->has('members', 1)
                ->where('members.0.user_id', $cashier->id)
                ->where('members.0.creator.id', $admin->id)
                ->where('members.0.updater.id', $admin->id)
            );
    }
}
