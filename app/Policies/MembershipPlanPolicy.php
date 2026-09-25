<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class MembershipPlanPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('memberships.view');
    }

    public function view(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('memberships.view') && $this->withinCompanyScope($user, $membershipPlan->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('memberships.manage');
    }

    public function update(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('memberships.manage') && $this->withinCompanyScope($user, $membershipPlan->company_id);
    }

    public function delete(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('memberships.manage') && $this->withinCompanyScope($user, $membershipPlan->company_id);
    }
}
