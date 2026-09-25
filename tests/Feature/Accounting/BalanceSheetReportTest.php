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

class BalanceSheetReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_balance_sheet_lists_assets_and_balances_to_liabilities_plus_equity(): void
    {
        $company = Company::factory()->create();
        $cash = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '1010',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
        ]);
        $inventory = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '1300',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
        ]);
        $ap = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '2100',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
        ]);
        $revenue = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '4010',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
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
            'debit' => 500,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $saleEntry->id,
            'chart_of_account_id' => $revenue->id,
            'line_number' => 2,
            'debit' => 0,
            'credit' => 500,
        ]);

        $receiptEntry = JournalEntry::factory()->create([
            'company_id' => $company->id,
            'entry_number' => 'JE-TEST-0002',
            'status' => 'posted',
            'posted_at' => now(),
            'entry_date' => now()->toDateString(),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $receiptEntry->id,
            'chart_of_account_id' => $inventory->id,
            'line_number' => 1,
            'debit' => 150,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $receiptEntry->id,
            'chart_of_account_id' => $ap->id,
            'line_number' => 2,
            'debit' => 0,
            'credit' => 150,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.accounting.balance-sheet.index'))
            ->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertSame('650.0000', $props['totals']['assets']);
        $this->assertSame('150.0000', $props['totals']['liabilities']);
        $this->assertSame('500.0000', $props['netIncome']);
        $this->assertSame('650.0000', $props['totals']['liabilities_and_equity']);
    }
}
