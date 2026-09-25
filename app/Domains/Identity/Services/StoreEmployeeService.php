<?php

namespace App\Domains\Identity\Services;

use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;

class StoreEmployeeService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /** @return list<array{id: string, store_name: string, store_code: string, company_id: string, members_count: int}> */
    public function storeSummaries(User $actor): array
    {
        return app(BackofficeContextService::class)
            ->applyStoreQueryScope(Store::query(), $actor)
            ->withCount('users as members_count')
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id'])
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_code' => $store->store_code,
                'company_id' => $store->company_id,
                'members_count' => (int) $store->members_count,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{
     *     user_id: string,
     *     store_id: string,
     *     user_name: string,
     *     email: string,
     *     status: string,
     *     roles: list<string>,
     *     creator: array{id: string, name: string}|null,
     *     updater: array{id: string, name: string}|null,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     stores: list<array{id: string, store_name: string, store_code: string}>
     * }> */
    public function listForStore(User $actor, string $storeId, ?string $search = null): array
    {
        return User::query()
            ->with([
                'roles:id,name',
                'creator:id,name',
                'updater:id,name',
                'stores:id,store_name,store_code',
            ])
            ->whereHas('stores', fn ($query) => $query->where('stores.id', $storeId))
            ->when(! $actor->hasGlobalOrganizationAccess(), fn ($query) => $query->where('company_id', $actor->company_id))
            ->when($search, fn ($query) => $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'])
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'store_id' => $storeId,
                'user_name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->roles->pluck('name')->values()->all(),
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

    /** @return list<array{id: string, name: string, email: string, status: string, roles: list<string>}> */
    public function availableUsers(User $actor, Store $store): array
    {
        $assignedIds = $store->users()->pluck('users.id');

        return User::query()
            ->with('roles:id,name')
            ->when(! $actor->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $actor->company_id))
            ->where(function ($query) use ($store) {
                $query->where('company_id', $store->company_id)
                    ->orWhereNull('company_id');
            })
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'company_id'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->roles->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @param  list<string>  $userIds
     * @param  list<string>  $roleNames
     */
    public function assignMany(
        User $actor,
        string $storeId,
        array $userIds,
        array $roleNames = [],
    ): int {
        $store = Store::query()->findOrFail($storeId);
        $this->assertActorCanManageStore($actor, $store);

        $users = User::query()->whereIn('id', $userIds)->get();
        $assigned = 0;

        foreach ($users as $user) {
            $this->assertUserInActorScope($actor, $user);
            $this->assertUserMatchesStoreCompany($user, $store);

            if ($user->stores()->where('stores.id', $store->id)->exists()) {
                continue;
            }

            $user->stores()->attach($store->id);

            if (! $user->default_store_id) {
                $user->update(['default_store_id' => $store->id]);
            }

            if ($roleNames !== []) {
                $user->syncRoles(array_values(array_unique([
                    ...$user->roles->pluck('name')->all(),
                    ...$roleNames,
                ])));
            }

            $this->auditLogger->log(
                'assign_store',
                'store_user',
                User::class,
                $user->id,
                null,
                ['store_id' => $store->id],
            );

            $assigned++;
        }

        return $assigned;
    }

    public function remove(User $actor, User $user, Store $store): void
    {
        $this->assertActorCanManageStore($actor, $store);
        $this->assertUserInActorScope($actor, $user);

        if (! $user->stores()->where('stores.id', $store->id)->exists()) {
            return;
        }

        $user->stores()->detach($store->id);

        if ($user->default_store_id === $store->id) {
            $user->update([
                'default_store_id' => $user->stores()->value('stores.id'),
            ]);
        }

        $this->auditLogger->log(
            'remove_store_assignment',
            'store_user',
            User::class,
            $user->id,
            ['store_id' => $store->id],
            null,
        );
    }

    protected function assertActorCanManageStore(User $actor, Store $store): void
    {
        if (! app(BackofficeContextService::class)->allowsStore($actor, $store)) {
            abort(403);
        }

        if ($actor->hasGlobalOrganizationAccess()) {
            return;
        }

        if ($actor->hasRole('Company Admin') && $actor->company_id === $store->company_id) {
            return;
        }

        if ($actor->canAccessStore($store)) {
            return;
        }

        abort(403);
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

    protected function assertUserMatchesStoreCompany(User $user, Store $store): void
    {
        if ($user->company_id && $user->company_id !== $store->company_id) {
            abort(422, 'The user must belong to the same company as the store.');
        }
    }
}
