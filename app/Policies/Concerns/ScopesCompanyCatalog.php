<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ScopesCompanyCatalog
{
    protected function withinCompanyScope(User $user, string $companyId): bool
    {
        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        return $user->canAccessCompany($companyId);
    }
}
