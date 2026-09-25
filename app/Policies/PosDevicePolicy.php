<?php

namespace App\Policies;

use App\Models\PosDevice;
use App\Models\User;

class PosDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sync.manage');
    }

    public function revoke(User $user, PosDevice $device): bool
    {
        if (! $user->can('sync.manage')) {
            return false;
        }

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($device->company_id);
        }

        $device->loadMissing('store');

        return $user->canAccessStore($device->store);
    }
}
