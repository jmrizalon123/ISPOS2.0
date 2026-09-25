<?php

namespace App\Domains\Sync\Services;

use App\Models\PosDevice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PosDeviceQueryService
{
    public function paginate(User $user, ?string $search = null, ?string $storeId = null, ?string $status = null): LengthAwarePaginator
    {
        return PosDevice::query()
            ->with(['store:id,store_name,store_code', 'register:id,register_name,register_code', 'registeredByUser:id,name'])
            ->when(! $user->hasGlobalOrganizationAccess(), function ($query) use ($user) {
                if ($user->hasRole('Company Admin')) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $storeIds = $user->stores()->pluck('stores.id');
                    $query->whereIn('store_id', $storeIds);
                }
            })
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('fingerprint', 'like', "%{$search}%");
            }))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('last_sync_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }
}
