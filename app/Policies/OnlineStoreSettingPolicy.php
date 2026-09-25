<?php

namespace App\Policies;

use App\Models\OnlineStoreSetting;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class OnlineStoreSettingPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('online_store.view');
    }

    public function view(User $user, OnlineStoreSetting $onlineStoreSetting): bool
    {
        return $user->can('online_store.view')
            && $this->withinCompanyScope($user, $onlineStoreSetting->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('online_store.manage');
    }

    public function update(User $user, OnlineStoreSetting $onlineStoreSetting): bool
    {
        return $user->can('online_store.manage')
            && $this->withinCompanyScope($user, $onlineStoreSetting->company_id);
    }

    public function delete(User $user, OnlineStoreSetting $onlineStoreSetting): bool
    {
        return $this->update($user, $onlineStoreSetting);
    }

    public function publish(User $user, OnlineStoreSetting $onlineStoreSetting): bool
    {
        return $this->update($user, $onlineStoreSetting);
    }
}
