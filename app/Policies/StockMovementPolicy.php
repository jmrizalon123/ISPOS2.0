<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $user->can('inventory.view') && $this->withinScope($user, $movement);
    }

    public function adjust(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    protected function withinScope(User $user, StockMovement $movement): bool
    {
        $movement->loadMissing('store');

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($movement->store->company_id);
        }

        return $user->canAccessStore($movement->store);
    }
}
