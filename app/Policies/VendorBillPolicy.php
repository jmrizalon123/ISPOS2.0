<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorBill;
use App\Policies\Concerns\ScopesCompanyCatalog;

class VendorBillPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('ap.view');
    }

    public function view(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('ap.view') && $this->withinCompanyScope($user, $vendorBill->company_id);
    }
}
