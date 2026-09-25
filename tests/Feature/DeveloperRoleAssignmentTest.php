<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeveloperRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_cannot_assign_developer_role(): void
    {
        $company = Company::factory()->create();
        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'New Developer',
                'email' => 'newdev@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'company_id' => $company->id,
                'status' => 'active',
                'roles' => ['Developer'],
                'store_ids' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'newdev@example.com')->first();
        $this->assertNotNull($created);
        $this->assertFalse($created->hasRole('Developer'));
    }

    public function test_developer_can_assign_developer_role(): void
    {
        $company = Company::factory()->create();
        $developer = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $developer->assignRole('Developer');

        $this->actingAs($developer)
            ->post(route('admin.users.store'), [
                'name' => 'Another Developer',
                'email' => 'anotherdev@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'company_id' => $company->id,
                'status' => 'active',
                'roles' => ['Developer'],
                'store_ids' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'anotherdev@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('Developer'));
    }

    public function test_super_admin_cannot_remove_developer_role_from_existing_user(): void
    {
        $company = Company::factory()->create();
        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $superAdmin->assignRole('Super Admin');

        $developerUser = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $developerUser->assignRole('Developer');

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $developerUser), [
                'name' => $developerUser->name,
                'email' => $developerUser->email,
                'company_id' => $company->id,
                'status' => 'active',
                'password' => '',
                'password_confirmation' => '',
                'roles' => ['Company Admin'],
                'store_ids' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $developerUser->refresh();
        $this->assertTrue($developerUser->hasRole('Developer'));
    }

    public function test_super_admin_cannot_edit_developer_role_permissions(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('Super Admin');

        $developerRole = Role::findByName('Developer');

        $this->actingAs($superAdmin)
            ->put(route('admin.roles.update', $developerRole), [
                'name' => 'Developer',
                'permissions' => ['dashboard.view'],
            ])
            ->assertForbidden();
    }

    public function test_developer_can_edit_developer_role_permissions(): void
    {
        $developer = User::factory()->create(['status' => 'active']);
        $developer->assignRole('Developer');

        $developerRole = Role::findByName('Developer');

        $this->actingAs($developer)
            ->put(route('admin.roles.update', $developerRole), [
                'name' => 'Developer',
                'permissions' => $developerRole->permissions->pluck('name')->all(),
            ])
            ->assertRedirect(route('admin.roles.index', ['role_id' => $developerRole->id]));
    }
}
