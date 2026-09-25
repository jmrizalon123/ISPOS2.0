<?php

namespace App\Policies;

use App\Models\SalesPlan;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class SalesPlanPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('sales_plans.view');
    }

    public function view(User $user, SalesPlan $salesPlan): bool
    {
        return $user->can('sales_plans.view') && $this->withinCompanyScope($user, $salesPlan->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('sales_plans.create');
    }

    public function update(User $user, SalesPlan $salesPlan): bool
    {
        return $user->can('sales_plans.update') && $this->withinCompanyScope($user, $salesPlan->company_id);
    }

    public function delete(User $user, SalesPlan $salesPlan): bool
    {
        return $user->can('sales_plans.delete') && $this->withinCompanyScope($user, $salesPlan->company_id);
    }
}
