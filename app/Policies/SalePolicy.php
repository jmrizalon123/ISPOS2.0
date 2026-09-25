<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function view(User $user, Sale $sale): bool
    {
        if (! $user->can('pos.access')) {
            return false;
        }

        return $this->withinScope($user, $sale);
    }

    public function void(User $user, Sale $sale): bool
    {
        if (! $user->can('pos.void')) {
            return false;
        }

        return $this->withinScope($user, $sale);
    }

    public function refund(User $user, Sale $sale): bool
    {
        if (! $user->can('pos.refund')) {
            return false;
        }

        return $this->withinScope($user, $sale);
    }

    protected function withinScope(User $user, Sale $sale): bool
    {
        $sale->loadMissing('store');

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($sale->store->company_id);
        }

        return $user->canAccessStore($sale->store);
    }
}
