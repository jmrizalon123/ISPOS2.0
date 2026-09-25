<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;

class PurchaseReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('purchasing.view') && $this->withinScope($user, $purchaseReturn);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.create');
    }

    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('purchasing.create')
            && $purchaseReturn->isDraft()
            && $this->withinScope($user, $purchaseReturn);
    }

    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('purchasing.create')
            && $purchaseReturn->isDraft()
            && $this->withinScope($user, $purchaseReturn);
    }

    public function post(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('purchasing.approve')
            && $purchaseReturn->isDraft()
            && $this->withinScope($user, $purchaseReturn);
    }

    protected function withinScope(User $user, PurchaseReturn $purchaseReturn): bool
    {
        $purchaseReturn->loadMissing('store');

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($purchaseReturn->store->company_id);
        }

        return $user->canAccessStore($purchaseReturn->store);
    }
}
