<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_cannot_view_chart_of_accounts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.chart-of-accounts.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_view_journal_entries(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->get(route('admin.journal-entries.index'))
            ->assertOk();
    }

    public function test_auditor_can_view_trial_balance_but_cannot_create_accounts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Auditor');

        $this->actingAs($user)
            ->get(route('admin.accounting.trial-balance.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.chart-of-accounts.create'))
            ->assertForbidden();
    }
}
