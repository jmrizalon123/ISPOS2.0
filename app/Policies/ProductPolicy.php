<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class ProductPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view') && $this->withinCompanyScope($user, $product->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update') && $this->withinCompanyScope($user, $product->company_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete') && $this->withinCompanyScope($user, $product->company_id);
    }
}
