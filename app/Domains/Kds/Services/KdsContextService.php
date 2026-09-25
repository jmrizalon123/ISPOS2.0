<?php

namespace App\Domains\Kds\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class KdsContextService
{
    public const SESSION_KEY = 'kds_context';

    public function getStoreId(): ?string
    {
        $ctx = Session::get(self::SESSION_KEY, []);

        return $ctx['store_id'] ?? null;
    }

    public function setStore(User $user, string $storeId): void
    {
        $store = Store::query()->findOrFail($storeId);

        if (! $user->canAccessStore($store)) {
            abort(403);
        }

        if ($store->status !== 'active') {
            throw ValidationException::withMessages(['store_id' => 'Store is not active.']);
        }

        Session::put(self::SESSION_KEY, ['store_id' => $store->id]);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function resolveStore(User $user): ?Store
    {
        $storeId = $this->getStoreId();
        if (! $storeId) {
            return null;
        }

        $store = Store::query()->find($storeId);
        if (! $store || ! $user->canAccessStore($store)) {
            return null;
        }

        return $store;
    }

    /** @return list<array{id: string, store_name: string, store_code: string}> */
    public function accessibleStores(User $user): array
    {
        $query = Store::query()->where('status', 'active')->orderBy('store_name');

        if (! $user->hasGlobalOrganizationAccess()) {
            if ($user->hasRole('Company Admin')) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereIn('id', $user->stores()->pluck('stores.id'));
            }
        }

        return $query->get(['id', 'store_name', 'store_code'])->map(fn ($store) => [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'store_code' => $store->store_code,
        ])->all();
    }
}
