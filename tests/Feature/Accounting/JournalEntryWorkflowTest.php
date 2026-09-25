<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_balanced_draft_can_be_posted(): void
    {
        $company = Company::factory()->create();
        $cash = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '1010',
            'account_type' => 'asset',
        ]);
        $expense = ChartOfAccount::factory()->create([
            'company_id' => $company->id,
            'account_code' => '5100',
            'account_type' => 'expense',
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->post(route('admin.journal-entries.store'), [
                'company_id' => $company->id,
                'entry_date' => now()->toDateString(),
                'description' => 'Office supplies',
                'lines' => [
                    ['chart_of_account_id' => $expense->id, 'debit' => 500, 'credit' => 0],
                    ['chart_of_account_id' => $cash->id, 'debit' => 0, 'credit' => 500],
                ],
            ])
            ->assertRedirect();

        $entry = JournalEntry::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('draft', $entry->status);

        $this->actingAs($user)
            ->post(route('admin.journal-entries.post', $entry))
            ->assertRedirect(route('admin.journal-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame('posted', $entry->status);
        $this->assertNotNull($entry->posted_at);
    }
}
