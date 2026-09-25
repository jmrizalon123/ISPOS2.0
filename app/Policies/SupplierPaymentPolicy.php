<?php

namespace App\Policies;

use App\Models\SupplierPayment;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class SupplierPaymentPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('ap.view');
    }

    public function view(User $user, SupplierPayment $supplierPayment): bool
    {
        return $user->can('ap.view') && $this->withinCompanyScope($user, $supplierPayment->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('ap.pay');
    }
}
