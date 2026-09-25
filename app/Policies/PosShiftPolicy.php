<?php

namespace App\Policies;

use App\Models\PosShift;
use App\Models\User;

class PosShiftPolicy
{
    public function create(User $user): bool
    {
        return $user->can('pos.access');
    }

    public function open(User $user): bool
    {
        return $user->can('pos.access');
    }

    public function close(User $user, PosShift $shift): bool
    {
        if (! $user->can('pos.access')) {
            return false;
        }

        return $this->withinScope($user, $shift);
    }

    public function view(User $user, PosShift $shift): bool
    {
        return $user->can('pos.access') && $this->withinScope($user, $shift);
    }

    protected function withinScope(User $user, PosShift $shift): bool
    {
        $shift->loadMissing('store');

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($shift->store->company_id);
        }

        return $user->canAccessStore($shift->store);
    }
}
