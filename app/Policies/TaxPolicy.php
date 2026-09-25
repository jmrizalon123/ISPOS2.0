<?php

namespace App\Policies;

use App\Models\Tax;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class TaxPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('taxes.view');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->can('taxes.view') && $this->withinCompanyScope($user, $tax->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('taxes.create');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->can('taxes.update') && $this->withinCompanyScope($user, $tax->company_id);
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->can('taxes.delete') && $this->withinCompanyScope($user, $tax->company_id);
    }
}
