<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Reporting\Services\Concerns\ScopesSalesReports;
use App\Models\SaleLine;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductSalesReportService
{
    use ScopesSalesReports;

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     * @return Collection<int, object{product_id: string|null, sku: string, name: string, qty_sold: string, revenue: string}>
     */
    public function topProducts(User $user, array $filters, int $limit = 25): Collection
    {
        $saleIds = $this->applyReportFilters(
            $this->scopedSalesQuery($user)->where('sales.status', 'completed'),
            $user,
            $filters,
        )->select('sales.id');

        return SaleLine::query()
            ->whereIn('sale_id', $saleIds)
            ->select([
                'product_id',
                'sku',
                'name',
                DB::raw('SUM(qty) as qty_sold'),
                DB::raw('SUM(line_total) as revenue'),
            ])
            ->groupBy('product_id', 'sku', 'name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }
}
