<?php

namespace App\Domains\Identity\Services;

use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Spatie\Permission\Models\Role;

class RoleMemberService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /** @return list<array{id: int, name: string, users_count: int, permissions_count: int}> */
    public function roleSummaries(User $actor): array
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->when(! $actor->isDeveloper(), fn ($query) => $query->where('name', '!=', 'Developer'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => (int) $role->users_count,
                'permissions_count' => (int) $role->permissions_count,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{
     *     user_id: string,
     *     role_id: int,
     *     user_name: string,
     *     email: string,
     *     status: string,
     *     creator: array{id: string, name: string}|null,
     *     updater: array{id: string, name: string}|null,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     stores: list<array{id: string, store_name: string, store_code: string}>
     * }> */
    public function listForRole(User $actor, int $roleId, ?string $search = null): array
    {
        $role = Role::query()->findOrFail($roleId);
        $this->assertActorCanManageRole($actor, $role);

        return User::query()
            ->role($role->name)
            ->with([
                'creator:id,name',
                'updater:id,name',
                'stores:id,store_name,store_code',
            ])
            ->when(! $actor->hasGlobalOrganizationAccess(), fn ($query) => $query->where('company_id', $actor->company_id))
            ->when($search, fn ($query) => $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'])
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'creator' => $user->creator
                    ? ['id' => $user->creator->id, 'name' => $user->creator->name]
                    : null,
                'updater' => $user->updater
                    ? ['id' => $user->updater->id, 'name' => $user->updater->name]
                    : null,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'stores' => $user->stores
                    ->sortBy('store_name')
                    ->map(fn (Store $store) => [
                        'id' => $store->id,
                        'store_name' => $store->store_name,
                        'store_code' => $store->store_code,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, name: string, email: string, status: string}> */
    public function availableUsers(User $actor, Role $role): array
    {
        $this->assertActorCanManageRole($actor, $role);

        return User::query()
            ->when(! $actor->hasGlobalOrganizationAccess(), fn ($query) => $query->where('company_id', $actor->company_id))
            ->whereDoesntHave('roles', fn ($query) => $query->whereKey($role->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ])
            ->values()
            ->all();
    }

    /** @param  list<string>  $userIds */
    public function assignMany(User $actor, int $roleId, array $userIds): int
    {
        $role = Role::query()->findOrFail($roleId);
        $this->assertActorCanManageRole($actor, $role);

        $users = User::query()->whereIn('id', $userIds)->get();
        $assigned = 0;

        foreach ($users as $user) {
            $this->assertUserInActorScope($actor, $user);

            if ($user->hasRole($role->name)) {
                continue;
            }

            $user->assignRole($role->name);

            $this->auditLogger->log(
                'assign_role',
                'model_has_roles',
                User::class,
                $user->id,
                null,
                ['role_id' => $role->id, 'role_name' => $role->name],
            );

            $assigned++;
        }

        return $assigned;
    }

    public function remove(User $actor, User $user, Role $role): void
    {
        $this->assertActorCanManageRole($actor, $role);
        $this->assertUserInActorScope($actor, $user);
        $this->assertCanRemoveRole($actor, $user, $role);

        if (! $user->hasRole($role->name)) {
            return;
        }

        $user->removeRole($role->name);

        $this->auditLogger->log(
            'remove_role',
            'model_has_roles',
            User::class,
            $user->id,
            ['role_id' => $role->id, 'role_name' => $role->name],
            null,
        );
    }

    protected function assertActorCanManageRole(User $actor, Role $role): void
    {
        if ($role->name === 'Super Admin' && ! $actor->isSuperAdmin()) {
            abort(403);
        }

        if ($role->name === 'Developer' && ! $actor->isDeveloper()) {
            abort(403);
        }
    }

    protected function assertUserInActorScope(User $actor, User $user): void
    {
        if ($actor->hasGlobalOrganizationAccess()) {
            return;
        }

        if ($actor->company_id !== $user->company_id) {
            abort(403);
        }
    }

    protected function assertCanRemoveRole(User $actor, User $user, Role $role): void
    {
        if ($role->name === 'Developer' && $user->hasRole('Developer') && ! $actor->isDeveloper()) {
            abort(403);
        }
    }
}
