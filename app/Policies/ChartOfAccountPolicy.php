<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class ChartOfAccountPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('accounting.view');
    }

    public function view(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->can('accounting.view') && $this->withinCompanyScope($user, $chartOfAccount->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.post');
    }

    public function update(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->can('accounting.post') && $this->withinCompanyScope($user, $chartOfAccount->company_id);
    }

    public function delete(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->can('accounting.post') && $this->withinCompanyScope($user, $chartOfAccount->company_id);
    }
}
