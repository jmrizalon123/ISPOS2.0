<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class PromotionPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('promotions.view');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.view') && $this->withinCompanyScope($user, $promotion->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('promotions.create');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.update') && $this->withinCompanyScope($user, $promotion->company_id);
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.update') && $this->withinCompanyScope($user, $promotion->company_id);
    }

    public function activate(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.active') && $this->withinCompanyScope($user, $promotion->company_id);
    }

    public function cancel(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.cancelled') && $this->withinCompanyScope($user, $promotion->company_id);
    }

    public function setStatus(User $user, ?string $status): bool
    {
        if (! is_string($status) || $status === '') {
            return false;
        }

        return in_array($status, ['draft', 'active', 'expired', 'cancelled'], true)
            && $user->can("promotions.{$status}");
    }
}
