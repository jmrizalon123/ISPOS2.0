<?php

namespace App\Domains\Inventory\Services;

use App\Models\StoreProductInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LowStockAlertService
{
    public function __construct(protected StoreInventoryQueryService $queryService) {}

    public function count(User $user, ?string $companyId = null, ?string $storeId = null): int
    {
        return $this->queryService->lowStockCount($user, $companyId, $storeId);
    }

    /** @return Collection<int, StoreProductInventory> */
    public function list(User $user, ?string $companyId = null, ?string $storeId = null, int $limit = 10): Collection
    {
        return $this->queryService->lowStockList($user, $companyId, $storeId, $limit);
    }

    /** @return Builder<StoreProductInventory> */
    public function baseQuery(User $user, ?string $companyId = null, ?string $storeId = null): Builder
    {
        return $this->queryService->lowStockQuery($user, $companyId, $storeId);
    }
}
