<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreInventoryQueryService
{
    /** @return Builder<StoreProductInventory> */
    public function inventoryQuery(User $user, ?string $companyId = null, ?string $storeId = null): Builder
    {
        $query = StoreProductInventory::query()
            ->with(['store:id,store_name,store_code', 'product:id,sku,name,warning_qty,ideal_qty,track_inventory,status']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (! $user->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $user->company_id);
        }

        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);

        if ($storeId) {
            $query->where('store_id', $storeId);
        } elseif ($contextStoreId) {
            $query->where('store_id', $contextStoreId);
        } elseif (! $user->hasGlobalOrganizationAccess() && ! $user->hasRole('Company Admin') && ! $user->isHeadOfficeBased()) {
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        }

        return $query;
    }

    public function paginateInventory(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        ?string $storeId = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->inventoryQuery($user, $companyId, $storeId);

        if ($search) {
            $query->whereHas('product', fn ($q) => $q->where('sku', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        return $query
            ->whereHas('product', fn ($q) => $q->where('track_inventory', true)->where('status', 'active'))
            ->orderBy('store_id')
            ->orderBy('product_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Builder<StockMovement> */
    public function movementQuery(User $user, ?string $companyId = null, ?string $storeId = null): Builder
    {
        $query = StockMovement::query()
            ->with([
                'store:id,store_name,store_code',
                'product:id,sku,name',
                'user:id,name',
                'sale:id,sale_number',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (! $user->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $user->company_id);
        }

        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);

        if ($storeId) {
            $query->where('store_id', $storeId);
        } elseif ($contextStoreId) {
            $query->where('store_id', $contextStoreId);
        } elseif (! $user->hasGlobalOrganizationAccess() && ! $user->hasRole('Company Admin') && ! $user->isHeadOfficeBased()) {
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        }

        return $query;
    }

    public function paginateMovements(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        ?string $storeId = null,
        ?string $movementType = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->movementQuery($user, $companyId, $storeId);

        if ($search) {
            $query->whereHas('product', fn ($q) => $q->where('sku', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /** @return Builder<StoreProductInventory> */
    public function lowStockQuery(User $user, ?string $companyId = null, ?string $storeId = null): Builder
    {
        return $this->inventoryQuery($user, $companyId, $storeId)
            ->whereHas('product', fn ($q) => $q->where('track_inventory', true)->where('status', 'active'))
            ->where(function ($query) {
                $query->where('qty', '<=', 0)
                    ->orWhereHas('product', function ($q) {
                        $q->whereNotNull('warning_qty')
                            ->whereColumn('store_product_inventories.qty', '<=', 'products.warning_qty');
                    });
            });
    }

    public function lowStockCount(User $user, ?string $companyId = null, ?string $storeId = null): int
    {
        return $this->lowStockQuery($user, $companyId, $storeId)->count();
    }

    /** @return Collection<int, StoreProductInventory> */
    public function lowStockList(User $user, ?string $companyId = null, ?string $storeId = null, int $limit = 10): Collection
    {
        return $this->lowStockQuery($user, $companyId, $storeId)
            ->orderBy('qty')
            ->limit($limit)
            ->get();
    }

    public function companyTotalQty(string $productId): string
    {
        return (string) StoreProductInventory::query()
            ->where('product_id', $productId)
            ->sum('qty');
    }
}
