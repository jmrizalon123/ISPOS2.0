<?php

namespace App\Domains\Organization\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\SalesPlan;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesPlanService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, ?string $storeId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            SalesPlan::class,
            ['plan_code', 'name', 'description'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status, $storeId) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->withCount(['stores', 'products'])
                    ->when($storeId, fn ($query) => $query->whereHas('stores', fn ($stores) => $stores->whereKey($storeId)))
                    ->orderBy('name');
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): SalesPlan
    {
        return DB::transaction(function () use ($data, $actor) {
            $storeIds = $this->extractStoreIds($data);
            $this->stampActor($data, $actor);

            $salesPlan = SalesPlan::create($data);
            $this->syncStores($salesPlan, $storeIds);
            $this->logCatalogCreate('sales_plans', SalesPlan::class, $salesPlan);

            return $salesPlan->fresh(['stores:id,store_name,store_code,sales_plan_id,company_id']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalesPlan $salesPlan, array $data, ?User $actor = null): SalesPlan
    {
        return DB::transaction(function () use ($salesPlan, $data, $actor) {
            $storeIds = $this->extractStoreIds($data);

            if ($actor) {
                $data['updated_by'] = $actor->id;
            }

            $old = $salesPlan->toArray();
            $salesPlan->update($data);
            $this->syncStores($salesPlan, $storeIds);
            $this->logCatalogUpdate('sales_plans', SalesPlan::class, $salesPlan, $old);

            return $salesPlan->fresh(['stores:id,store_name,store_code,sales_plan_id,company_id']);
        });
    }

    public function delete(SalesPlan $salesPlan): void
    {
        $this->logCatalogDelete('sales_plans', SalesPlan::class, $salesPlan);
        $salesPlan->delete();
    }

    /**
     * @param  list<string>  $storeIds
     */
    public function syncStores(SalesPlan $salesPlan, array $storeIds): void
    {
        Store::query()
            ->where('company_id', $salesPlan->company_id)
            ->where('sales_plan_id', $salesPlan->id)
            ->when($storeIds !== [], fn ($q) => $q->whereNotIn('id', $storeIds))
            ->update(['sales_plan_id' => null]);

        if ($storeIds === []) {
            return;
        }

        Store::query()
            ->where('company_id', $salesPlan->company_id)
            ->whereIn('id', $storeIds)
            ->update(['sales_plan_id' => $salesPlan->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function extractStoreIds(array &$data): array
    {
        $storeIds = array_values(array_filter(
            array_map('strval', $data['store_ids'] ?? []),
            fn (string $id) => $id !== '',
        ));

        unset($data['store_ids']);

        return $storeIds;
    }
}
