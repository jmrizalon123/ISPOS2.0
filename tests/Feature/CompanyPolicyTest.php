<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_developer_can_create_company(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Developer');

        $this->actingAs($user)
            ->post(route('admin.companies.store'), [
                'company_code' => 'ACME',
                'name' => 'Acme Retail',
                'timezone' => 'Asia/Manila',
                'base_currency' => 'PHP',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.companies.index'));

        $this->assertDatabaseHas('companies', ['company_code' => 'ACME']);
    }

    public function test_super_admin_cannot_create_company(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->post(route('admin.companies.store'), [
                'company_code' => 'NOPE',
                'name' => 'Nope',
                'timezone' => 'Asia/Manila',
                'base_currency' => 'PHP',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_company_admin_cannot_create_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Company Admin');

        $this->actingAs($user)
            ->post(route('admin.companies.store'), [
                'company_code' => 'NOPE',
                'name' => 'Nope',
                'timezone' => 'Asia/Manila',
                'base_currency' => 'PHP',
                'status' => 'active',
            ])
            ->assertForbidden();
    }
}
