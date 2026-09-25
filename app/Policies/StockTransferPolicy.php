<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        if (! $user->can('inventory.view')) {
            return false;
        }

        return $this->withinScope($user, $transfer);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    protected function withinScope(User $user, StockTransfer $transfer): bool
    {
        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->company_id === $transfer->company_id;
        }

        return $user->canAccessStore($transfer->from_store_id)
            || $user->canAccessStore($transfer->to_store_id)
            || ($transfer->from_warehouse_id && $user->canAccessStore($transfer->from_warehouse_id))
            || ($transfer->to_warehouse_id && $user->canAccessStore($transfer->to_warehouse_id));
    }
}
