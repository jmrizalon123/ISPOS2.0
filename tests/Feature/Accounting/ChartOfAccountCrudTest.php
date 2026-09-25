<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_accountant_can_create_chart_of_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->post(route('admin.chart-of-accounts.store'), [
                'company_id' => $company->id,
                'account_code' => '1200',
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.chart-of-accounts.index'));

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $company->id,
            'account_code' => '1200',
            'account_name' => 'Accounts Receivable',
        ]);
    }
}
