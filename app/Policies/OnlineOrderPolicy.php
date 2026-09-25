<?php

namespace App\Policies;

use App\Models\OnlineOrder;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class OnlineOrderPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('online_store.view');
    }

    public function view(User $user, OnlineOrder $onlineOrder): bool
    {
        return $user->can('online_store.view') && $this->withinScope($user, $onlineOrder);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, OnlineOrder $onlineOrder): bool
    {
        return $user->can('online_store.manage_orders') && $this->withinScope($user, $onlineOrder);
    }

    public function delete(User $user, OnlineOrder $onlineOrder): bool
    {
        return false;
    }

    public function accept(User $user, OnlineOrder $onlineOrder): bool
    {
        return $this->update($user, $onlineOrder);
    }

    public function reject(User $user, OnlineOrder $onlineOrder): bool
    {
        return $this->update($user, $onlineOrder);
    }

    public function markReady(User $user, OnlineOrder $onlineOrder): bool
    {
        return $this->update($user, $onlineOrder);
    }

    public function complete(User $user, OnlineOrder $onlineOrder): bool
    {
        return $this->update($user, $onlineOrder);
    }

    public function cancel(User $user, OnlineOrder $onlineOrder): bool
    {
        return $this->update($user, $onlineOrder);
    }

    protected function withinScope(User $user, OnlineOrder $order): bool
    {
        if (! $this->withinCompanyScope($user, $order->company_id)) {
            return false;
        }

        return $user->canAccessStore($order->store_id);
    }
}
