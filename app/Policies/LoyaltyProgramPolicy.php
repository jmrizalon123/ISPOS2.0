<?php

namespace App\Policies;

use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class LoyaltyProgramPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('loyalty.view');
    }

    public function view(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty.view') && $this->withinCompanyScope($user, $loyaltyProgram->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('loyalty.manage');
    }

    public function update(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty.manage') && $this->withinCompanyScope($user, $loyaltyProgram->company_id);
    }

    public function delete(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty.manage') && $this->withinCompanyScope($user, $loyaltyProgram->company_id);
    }
}
