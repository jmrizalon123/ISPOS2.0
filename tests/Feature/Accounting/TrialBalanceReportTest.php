<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBalanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_trial_balance_lists_posted_account_totals(): void
    {
        $company = Company::factory()->create();
        $cash = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '1010',
            'account_type' => 'asset',
        ]);
        $revenue = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '4010',
            'account_type' => 'revenue',
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $entry = JournalEntry::factory()->create([
            'company_id' => $company->id,
            'status' => 'posted',
            'posted_at' => now(),
            'entry_date' => now()->toDateString(),
        ]);

        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $cash->id,
            'line_number' => 1,
            'debit' => 500,
            'credit' => 0,
        ]);

        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $revenue->id,
            'line_number' => 2,
            'debit' => 0,
            'credit' => 500,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.accounting.trial-balance.index'))
            ->assertOk();

        $rows = collect($response->viewData('page')['props']['rows']);
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->pluck('account_code')->contains('1010'));
    }
}
