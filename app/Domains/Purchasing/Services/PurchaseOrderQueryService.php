<?php

namespace App\Domains\Purchasing\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderQueryService
{
    /** @return Builder<PurchaseOrder> */
    public function baseQuery(User $user, ?string $companyId = null, ?string $storeId = null): Builder
    {
        $query = PurchaseOrder::query()
            ->with(['supplier:id,name,supplier_code', 'store:id,store_name,store_code', 'creator:id,name']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (! $user->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $user->company_id);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        } elseif (! $user->hasGlobalOrganizationAccess() && ! $user->hasRole('Company Admin')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        }

        return $query;
    }

    public function paginate(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        ?string $storeId = null,
        ?string $status = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->baseQuery($user, $companyId, $storeId)
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
