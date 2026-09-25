<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view') && $user->canAccessCompany($company);
    }

    public function create(User $user): bool
    {
        return $user->can('companies.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.update') && $user->canAccessCompany($company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.delete') && $user->canAccessCompany($company);
    }
}
