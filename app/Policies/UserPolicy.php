<?php

namespace App\Policies;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $actor, User $model): bool
    {
        if (! $actor->can('users.view')) {
            return false;
        }

        if (! app(BackofficeContextService::class)->userInScope($actor, $model)) {
            return false;
        }

        if ($actor->hasGlobalOrganizationAccess()) {
            return true;
        }

        return $actor->company_id && $actor->company_id === $model->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $actor, User $model): bool
    {
        if (! $actor->can('users.update')) {
            return false;
        }

        if (! app(BackofficeContextService::class)->userInScope($actor, $model)) {
            return false;
        }

        if ($actor->hasGlobalOrganizationAccess()) {
            return true;
        }

        return $actor->company_id && $actor->company_id === $model->company_id;
    }

    public function delete(User $actor, User $model): bool
    {
        if ($actor->id === $model->id) {
            return false;
        }

        if (! $actor->can('users.delete')) {
            return false;
        }

        if (! app(BackofficeContextService::class)->userInScope($actor, $model)) {
            return false;
        }

        if ($actor->hasGlobalOrganizationAccess()) {
            return true;
        }

        return $actor->company_id && $actor->company_id === $model->company_id;
    }
}
