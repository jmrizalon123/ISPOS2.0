<?php

namespace App\Domains\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialBalanceReportService
{
    /**
     * @return Collection<int, object{
     *     account_id: string,
     *     account_code: string,
     *     account_name: string,
     *     account_type: string,
     *     debit_total: string,
     *     credit_total: string,
     *     balance: string
     * }>
     */
    public function trialBalance(User $user, ?string $companyId, ?string $dateFrom, ?string $dateTo): Collection
    {
        $companyId = $this->resolveCompanyId($user, $companyId);

        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted');

        if ($dateFrom) {
            $query->whereDate('journal_entries.entry_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('journal_entries.entry_date', '<=', $dateTo);
        }

        $rows = $query
            ->select([
                'chart_of_accounts.id as account_id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
                DB::raw('SUM(journal_entry_lines.debit) as debit_total'),
                DB::raw('SUM(journal_entry_lines.credit) as credit_total'),
            ])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
            )
            ->orderBy('chart_of_accounts.account_code')
            ->get();

        return $rows->map(function ($row) {
            $debit = (string) ($row->debit_total ?? '0');
            $credit = (string) ($row->credit_total ?? '0');
            $balance = $row->normal_balance === 'debit'
                ? bcsub($debit, $credit, 4)
                : bcsub($credit, $debit, 4);

            return (object) [
                'account_id' => $row->account_id,
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'account_type' => $row->account_type,
                'debit_total' => $debit,
                'credit_total' => $credit,
                'balance' => $balance,
            ];
        });
    }

    /** @return list<ChartOfAccount> */
    public function activeAccounts(User $user, ?string $companyId): Collection
    {
        $companyId = $this->resolveCompanyId($user, $companyId);

        return ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type']);
    }

    protected function resolveCompanyId(User $user, ?string $companyId): string
    {
        if ($user->hasGlobalOrganizationAccess() && $companyId) {
            return $companyId;
        }

        return $user->company_id ?? $companyId ?? '';
    }
}
