<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_manager_can_edit_register_in_assigned_store(): void
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

        $otherStore = Store::create([
            'company_id' => $company->id,
            'store_code' => 'S2',
            'store_name' => 'Store 2',
            'currency' => 'PHP',
            'timezone' => 'Asia/Manila',
            'status' => 'active',
        ]);

        $register = Register::create([
            'store_id' => $store->id,
            'register_code' => 'R1',
            'register_name' => 'Register 1',
            'status' => 'active',
        ]);

        $otherRegister = Register::create([
            'store_id' => $otherStore->id,
            'register_code' => 'R1',
            'register_name' => 'Register 2',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Store Manager');
        $user->stores()->sync([$store->id]);

        $this->actingAs($user)
            ->get(route('admin.registers.edit', $register))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.registers.edit', $otherRegister))
            ->assertForbidden();
    }
}
