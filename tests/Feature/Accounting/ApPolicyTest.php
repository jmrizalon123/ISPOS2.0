<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_cannot_view_vendor_bills(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.vendor-bills.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_view_ap_aging_and_record_payments(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->get(route('admin.accounting.ap-aging.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.supplier-payments.create'))
            ->assertOk();
    }

    public function test_auditor_can_view_ap_but_cannot_record_payments(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Auditor');

        $this->actingAs($user)
            ->get(route('admin.vendor-bills.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.supplier-payments.create'))
            ->assertForbidden();
    }
}
