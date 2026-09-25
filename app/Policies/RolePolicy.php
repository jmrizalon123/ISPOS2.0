<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        if (! $user->can('roles.update')) {
            return false;
        }

        if ($role->name === 'Super Admin') {
            return $user->isSuperAdmin();
        }

        if ($role->name === 'Developer') {
            return $user->isDeveloper();
        }

        return ! $user->isDeveloper() || $user->isSuperAdmin();
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! in_array($role->name, $this->protectedRoles(), true);
    }

    /** @return list<string> */
    protected function protectedRoles(): array
    {
        return ['Super Admin', 'Developer'];
    }
}
