<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.view') && $this->withinScope($user, $purchaseOrder);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.create')
            && $purchaseOrder->isDraft()
            && $this->withinScope($user, $purchaseOrder);
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.create')
            && $purchaseOrder->isDraft()
            && $this->withinScope($user, $purchaseOrder);
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.approve')
            && $purchaseOrder->isDraft()
            && $this->withinScope($user, $purchaseOrder);
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.create')
            && $purchaseOrder->isReceivable()
            && $this->withinScope($user, $purchaseOrder);
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchasing.approve')
            && in_array($purchaseOrder->status, ['approved', 'partially_received'], true)
            && $this->withinScope($user, $purchaseOrder);
    }

    protected function withinScope(User $user, PurchaseOrder $purchaseOrder): bool
    {
        $purchaseOrder->loadMissing('store');

        if ($user->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($user->hasRole('Company Admin')) {
            return $user->canAccessCompany($purchaseOrder->store->company_id);
        }

        return $user->canAccessStore($purchaseOrder->store);
    }
}
