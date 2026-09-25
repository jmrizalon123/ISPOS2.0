<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_cannot_access_unauthorized_store_admin_pages(): void
    {
        $company = Company::create([
            'company_code' => 'C1',
            'name' => 'Company 1',
            'timezone' => 'Asia/Manila',
            'base_currency' => 'PHP',
            'status' => 'active',
        ]);

        $authorizedStore = Store::create([
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

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Store Manager');
        $user->stores()->sync([$authorizedStore->id]);

        $this->actingAs($user)
            ->get(route('admin.stores.edit', $authorizedStore))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.stores.edit', $otherStore))
            ->assertForbidden();
    }
}
