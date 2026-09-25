<?php

namespace App\Domains\Accounting\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialStatementReportService
{
    /**
     * @return array{
     *     revenue: Collection<int, object>,
     *     expenses: Collection<int, object>,
     *     totals: array{revenue: string, expenses: string, net_income: string}
     * }
     */
    public function profitAndLoss(User $user, ?string $companyId, ?string $dateFrom, ?string $dateTo): array
    {
        $revenue = $this->periodActivity($user, $companyId, $dateFrom, $dateTo, ['revenue']);
        $expenses = $this->periodActivity($user, $companyId, $dateFrom, $dateTo, ['expense']);

        $totalRevenue = $revenue->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $totalExpenses = $expenses->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $netIncome = bcsub($totalRevenue, $totalExpenses, 4);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totals' => [
                'revenue' => $totalRevenue,
                'expenses' => $totalExpenses,
                'net_income' => $netIncome,
            ],
        ];
    }

    /**
     * @return array{
     *     assets: Collection<int, object>,
     *     liabilities: Collection<int, object>,
     *     equity: Collection<int, object>,
     *     totals: array{
     *         assets: string,
     *         liabilities: string,
     *         equity: string,
     *         net_income: string,
     *         liabilities_and_equity: string
     *     }
     * }
     */
    public function balanceSheet(User $user, ?string $companyId, string $asOfDate): array
    {
        $assets = $this->balanceAsOf($user, $companyId, $asOfDate, ['asset']);
        $liabilities = $this->balanceAsOf($user, $companyId, $asOfDate, ['liability']);
        $equity = $this->balanceAsOf($user, $companyId, $asOfDate, ['equity']);

        $netIncome = $this->cumulativeNetIncome($user, $companyId, $asOfDate);

        $totalAssets = $assets->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $totalLiabilities = $liabilities->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $totalEquity = $equity->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $liabilitiesAndEquity = bcadd(bcadd($totalLiabilities, $totalEquity, 4), $netIncome, 4);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'net_income' => $netIncome,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'net_income' => $netIncome,
                'liabilities_and_equity' => $liabilitiesAndEquity,
            ],
        ];
    }

    /** @param  list<string>  $types */
    protected function periodActivity(User $user, ?string $companyId, ?string $dateFrom, ?string $dateTo, array $types): Collection
    {
        return $this->queryBalances($user, $companyId, $dateFrom, $dateTo, $types)
            ->filter(fn ($row) => bccomp($row->balance, '0', 4) !== 0)
            ->values();
    }

    /** @param  list<string>  $types */
    protected function balanceAsOf(User $user, ?string $companyId, string $asOfDate, array $types): Collection
    {
        return $this->queryBalances($user, $companyId, null, $asOfDate, $types)
            ->filter(fn ($row) => bccomp($row->balance, '0', 4) !== 0)
            ->values();
    }

    protected function cumulativeNetIncome(User $user, ?string $companyId, string $asOfDate): string
    {
        $revenue = $this->queryBalances($user, $companyId, null, $asOfDate, ['revenue'])
            ->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');
        $expenses = $this->queryBalances($user, $companyId, null, $asOfDate, ['expense'])
            ->reduce(fn ($carry, $row) => bcadd($carry, $row->balance, 4), '0');

        return bcsub($revenue, $expenses, 4);
    }

    /** @param  list<string>  $types */
    protected function queryBalances(
        User $user,
        ?string $companyId,
        ?string $dateFrom,
        ?string $dateTo,
        array $types,
    ): Collection {
        $companyId = $this->resolveCompanyId($user, $companyId);

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->whereIn('chart_of_accounts.account_type', $types);

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
                'balance' => $balance,
            ];
        });
    }

    protected function resolveCompanyId(User $user, ?string $companyId): string
    {
        if ($user->hasGlobalOrganizationAccess() && $companyId) {
            return $companyId;
        }

        return $user->company_id ?? $companyId ?? '';
    }
}
