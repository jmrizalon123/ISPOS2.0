<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class BrandPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('brands.view');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('brands.view') && $this->withinCompanyScope($user, $brand->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('brands.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('brands.update') && $this->withinCompanyScope($user, $brand->company_id);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('brands.delete') && $this->withinCompanyScope($user, $brand->company_id);
    }
}
