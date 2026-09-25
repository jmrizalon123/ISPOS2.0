<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class SupplierPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.view') && $this->withinCompanyScope($user, $supplier->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.create') && $this->withinCompanyScope($user, $supplier->company_id);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.approve') && $this->withinCompanyScope($user, $supplier->company_id);
    }
}
