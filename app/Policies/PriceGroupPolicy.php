<?php

namespace App\Policies;

use App\Models\PriceGroup;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class PriceGroupPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('price_groups.view');
    }

    public function view(User $user, PriceGroup $priceGroup): bool
    {
        return $user->can('price_groups.view') && $this->withinCompanyScope($user, $priceGroup->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('price_groups.create');
    }

    public function update(User $user, PriceGroup $priceGroup): bool
    {
        return $user->can('price_groups.update') && $this->withinCompanyScope($user, $priceGroup->company_id);
    }

    public function delete(User $user, PriceGroup $priceGroup): bool
    {
        return $user->can('price_groups.delete') && $this->withinCompanyScope($user, $priceGroup->company_id);
    }
}
