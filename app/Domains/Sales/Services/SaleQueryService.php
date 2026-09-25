<?php

namespace App\Domains\Sales\Services;

use App\Domains\Reporting\Services\SalesSummaryReportService;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;

class SaleQueryService
{
    public function __construct(protected SalesSummaryReportService $summaryReportService)
    {
    }

    /** @return array{today_sales: float, transactions: int, average_transaction: float} */
    public function todayStats(User $user): array
    {
        $summary = $this->summaryReportService->summary($user, [
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
            'status' => 'completed',
        ]);

        return [
            'today_sales' => (float) $summary['gross_sales'],
            'transactions' => $summary['transactions'],
            'average_transaction' => (float) $summary['average_ticket'],
        ];
    }

    /** @return list<array{date: string, total: string, count: int}> */
    public function last14DayTrend(User $user): array
    {
        return $this->summaryReportService->dailyTrend($user, [
            'date_from' => today()->subDays(13)->toDateString(),
            'date_to' => today()->toDateString(),
            'status' => 'completed',
        ], 14);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function refundableForStore(Store $store, ?string $search = null, int $limit = 20): Collection
    {
        $query = Sale::query()
            ->with(['user:id,name', 'customer:id,first_name,last_name'])
            ->where('store_id', $store->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit($limit);

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('sale_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get()->map(fn (Sale $sale) => [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'grand_total' => (string) $sale->grand_total,
            'completed_at' => $sale->completed_at?->toIso8601String(),
            'cashier_name' => $sale->user?->name,
            'customer_name' => $sale->customer?->displayName(),
        ]);
    }
}
