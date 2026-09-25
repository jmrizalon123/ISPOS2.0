<?php

namespace App\Domains\Organization\Services;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);

        return Store::query()
            ->with(['company:id,name,company_code,display_name', 'salesPlan:id,name,plan_code', 'creator:id,name', 'updater:id,name'])
            ->when($contextStoreId, fn ($q) => $q->whereKey($contextStoreId))
            ->when(! $contextStoreId && ! $user->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $user->company_id))
            ->when(
                ! $contextStoreId
                    && ! $user->hasGlobalOrganizationAccess()
                    && ! $user->hasRole('Company Admin')
                    && ! $user->isHeadOfficeBased(),
                fn ($q) => $q->whereIn('id', $user->stores()->pluck('stores.id')),
            )
            ->when($companyId && ! $contextStoreId, fn ($q) => $q->where('company_id', $companyId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('branch_code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->orderBy('store_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?User $actor = null): Store
    {
        if ($actor) {
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;
        }

        $store = Store::create($data);

        $this->auditLogger->log('create', 'stores', Store::class, $store->id, null, $store->toArray());

        return $store;
    }

    public function update(Store $store, array $data, ?User $actor = null): Store
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $store->toArray();
        $store->update($data);

        $this->auditLogger->log('update', 'stores', Store::class, $store->id, $old, $store->fresh()->toArray());

        return $store->fresh();
    }

    public function delete(Store $store): void
    {
        $old = $store->toArray();
        $store->delete();

        $this->auditLogger->log('delete', 'stores', Store::class, $store->id, $old, null);
    }
}
