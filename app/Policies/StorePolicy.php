<?php

namespace App\Policies;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stores.view');
    }

    public function view(User $user, Store $store): bool
    {
        return $user->can('stores.view') && $this->withinScope($user, $store);
    }

    public function create(User $user): bool
    {
        return $user->can('stores.create');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->can('stores.update') && $this->withinScope($user, $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->can('stores.delete') && $this->withinScope($user, $store);
    }

    protected function withinScope(User $user, Store $store): bool
    {
        if (! app(BackofficeContextService::class)->allowsStore($user, $store)) {
            return false;
        }

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($store->company_id);
        }

        return $user->canAccessStore($store);
    }
}
