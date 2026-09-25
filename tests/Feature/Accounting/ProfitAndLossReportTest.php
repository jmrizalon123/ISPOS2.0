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

class ProfitAndLossReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_profit_and_loss_shows_revenue_expenses_and_net_income(): void
    {
        $company = Company::factory()->create();
        $revenue = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '4010',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
        ]);
        $expense = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '5100',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
        ]);
        $cash = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '1010',
            'account_type' => 'asset',
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $saleEntry = JournalEntry::factory()->create([
            'company_id' => $company->id,
            'entry_number' => 'JE-TEST-0001',
            'status' => 'posted',
            'posted_at' => now(),
            'entry_date' => now()->toDateString(),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $saleEntry->id,
            'chart_of_account_id' => $cash->id,
            'line_number' => 1,
            'debit' => 1000,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $saleEntry->id,
            'chart_of_account_id' => $revenue->id,
            'line_number' => 2,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $expenseEntry = JournalEntry::factory()->create([
            'company_id' => $company->id,
            'entry_number' => 'JE-TEST-0002',
            'status' => 'posted',
            'posted_at' => now(),
            'entry_date' => now()->toDateString(),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $expenseEntry->id,
            'chart_of_account_id' => $expense->id,
            'line_number' => 1,
            'debit' => 200,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $expenseEntry->id,
            'chart_of_account_id' => $cash->id,
            'line_number' => 2,
            'debit' => 0,
            'credit' => 200,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.accounting.profit-and-loss.index'))
            ->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertSame('1000.0000', $props['totals']['revenue']);
        $this->assertSame('200.0000', $props['totals']['expenses']);
        $this->assertSame('800.0000', $props['totals']['net_income']);
    }
}
