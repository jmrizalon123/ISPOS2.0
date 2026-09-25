<?php

namespace Tests\Feature\Purchasing;

use App\Models\Company;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_cannot_access_purchasing(): void
    {
        $company = Company::factory()->create();
        Store::factory()->create(['company_id' => $company->id]);

        $cashier = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier)
            ->get(route('admin.suppliers.index'))
            ->assertForbidden();
    }

    public function test_purchasing_officer_can_access_suppliers(): void
    {
        $company = Company::factory()->create();

        $officer = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $officer->assignRole('Purchasing Officer');

        $this->actingAs($officer)
            ->get(route('admin.suppliers.index'))
            ->assertOk();
    }
}
