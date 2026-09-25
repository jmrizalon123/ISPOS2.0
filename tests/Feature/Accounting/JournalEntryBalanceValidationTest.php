<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryBalanceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $company = Company::factory()->create();
        $cash = ChartOfAccount::factory()->create(['company_id' => $company->id, 'account_type' => 'asset']);
        $revenue = ChartOfAccount::factory()->create(['company_id' => $company->id, 'account_type' => 'revenue']);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->from(route('admin.journal-entries.create'))
            ->post(route('admin.journal-entries.store'), [
                'company_id' => $company->id,
                'entry_date' => now()->toDateString(),
                'description' => 'Unbalanced test',
                'lines' => [
                    ['chart_of_account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                    ['chart_of_account_id' => $revenue->id, 'debit' => 0, 'credit' => 400],
                ],
            ])
            ->assertSessionHasErrors('lines');
    }
}
