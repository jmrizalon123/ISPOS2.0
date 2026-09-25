<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class UnitPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('units.view') && $this->withinCompanyScope($user, $unit->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('units.update') && $this->withinCompanyScope($user, $unit->company_id);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('units.delete') && $this->withinCompanyScope($user, $unit->company_id);
    }
}
