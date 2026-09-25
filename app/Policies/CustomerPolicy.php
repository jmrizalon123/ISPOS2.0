<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class CustomerPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view') && $this->withinCompanyScope($user, $customer->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update') && $this->withinCompanyScope($user, $customer->company_id);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.update') && $this->withinCompanyScope($user, $customer->company_id);
    }

    public function adjustLoyalty(User $user, Customer $customer): bool
    {
        return $user->can('loyalty.manage') && $this->withinCompanyScope($user, $customer->company_id);
    }

    public function assignMembership(User $user, Customer $customer): bool
    {
        return $user->can('memberships.manage') && $this->withinCompanyScope($user, $customer->company_id);
    }
}
