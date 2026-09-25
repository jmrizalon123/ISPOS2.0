<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class CategoryPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view') && $this->withinCompanyScope($user, $category->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.update') && $this->withinCompanyScope($user, $category->company_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete') && $this->withinCompanyScope($user, $category->company_id);
    }
}
