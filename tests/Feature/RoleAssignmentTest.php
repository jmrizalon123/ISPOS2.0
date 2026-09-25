<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_assign_roles_when_creating_user(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Cashier',
                'email' => 'newcashier@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'company_id' => $company->id,
                'status' => 'active',
                'roles' => ['Cashier'],
                'store_ids' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'newcashier@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('Cashier'));
    }
}
