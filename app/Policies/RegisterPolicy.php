<?php

namespace App\Policies;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Register;
use App\Models\User;

class RegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('registers.view');
    }

    public function view(User $user, Register $register): bool
    {
        return $user->can('registers.view') && $this->withinScope($user, $register);
    }

    public function create(User $user): bool
    {
        return $user->can('registers.create');
    }

    public function update(User $user, Register $register): bool
    {
        return $user->can('registers.update') && $this->withinScope($user, $register);
    }

    public function delete(User $user, Register $register): bool
    {
        return $user->can('registers.delete') && $this->withinScope($user, $register);
    }

    protected function withinScope(User $user, Register $register): bool
    {
        $register->loadMissing('store');

        if (! app(BackofficeContextService::class)->allowsStore($user, $register->store)) {
            return false;
        }

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($register->store->company_id);
        }

        return $user->canAccessStore($register->store);
    }
}
