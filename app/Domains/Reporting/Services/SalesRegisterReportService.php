<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Reporting\Services\Concerns\ScopesSalesReports;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SalesRegisterReportService
{
    use ScopesSalesReports;

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     */
    public function paginate(User $user, array $filters, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($user, $filters, $search)
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<\App\Models\Sale> */
    public function baseQuery(User $user, array $filters, ?string $search = null)
    {
        return $this->applyReportFilters(
            $this->scopedSalesQuery($user),
            $user,
            $filters,
        )
            ->with([
                'store:id,store_name,store_code',
                'register:id,register_name,register_code',
                'user:id,name',
                'customer:id,first_name,last_name,customer_code',
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('sale_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('completed_at');
    }
}
