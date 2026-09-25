<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Reporting\Services\Concerns\ScopesSalesReports;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalesSummaryReportService
{
    use ScopesSalesReports;

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     * @return array{
     *     gross_sales: string,
     *     discount_total: string,
     *     tax_total: string,
     *     net_sales: string,
     *     transactions: int,
     *     voided_count: int,
     *     average_ticket: string
     * }
     */
    public function summary(User $user, array $filters): array
    {
        $completedQuery = $this->applyReportFilters(
            $this->scopedSalesQuery($user)->where('sales.status', 'completed'),
            $user,
            $filters,
        );

        $transactions = (clone $completedQuery)->count();
        $grossSales = (string) (clone $completedQuery)->sum('grand_total');
        $discountTotal = (string) (clone $completedQuery)->sum('discount_total');
        $taxTotal = (string) (clone $completedQuery)->sum('tax_total');

        $voidedCount = $this->applyReportFilters(
            $this->scopedSalesQuery($user)->where('sales.status', 'voided'),
            $user,
            $filters,
        )->count();

        $average = $transactions > 0
            ? bcdiv($grossSales, (string) $transactions, 4)
            : '0.0000';

        return [
            'gross_sales' => $grossSales,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'net_sales' => $grossSales,
            'transactions' => $transactions,
            'voided_count' => $voidedCount,
            'average_ticket' => $average,
        ];
    }

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     * @return list<array{date: string, total: string, count: int}>
     */
    public function dailyTrend(User $user, array $filters, int $days = 14): array
    {
        $end = ! empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])
            : today();

        $start = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])
            : $end->copy()->subDays($days - 1);

        if ($start->diffInDays($end) + 1 > $days) {
            $start = $end->copy()->subDays($days - 1);
        }

        $rows = $this->applyReportFilters(
            $this->scopedSalesQuery($user)->where('sales.status', 'completed'),
            $user,
            array_merge($filters, [
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
            ]),
        )
            ->selectRaw('DATE(completed_at) as sale_date, SUM(grand_total) as total, COUNT(*) as count')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $trend = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);
            $trend[] = [
                'date' => $key,
                'total' => (string) ($row->total ?? '0'),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     * @return Collection<int, object{store_id: string, store_name: string, store_code: string, total: string, count: int}>
     */
    public function byStore(User $user, array $filters, int $limit = 10): Collection
    {
        return $this->applyReportFilters(
            $this->scopedSalesQuery($user)->where('sales.status', 'completed'),
            $user,
            $filters,
        )
            ->join('stores', 'stores.id', '=', 'sales.store_id')
            ->selectRaw('stores.id as store_id, stores.store_name, stores.store_code, SUM(sales.grand_total) as total, COUNT(*) as count')
            ->groupBy('stores.id', 'stores.store_name', 'stores.store_code')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }
}
