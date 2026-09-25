<?php

namespace App\Domains\Identity\Services;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function paginate(User $actor, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['company:id,name', 'roles:id,name', 'stores:id,store_name,store_code', 'creator:id,name', 'updater:id,name']);

        app(BackofficeContextService::class)->applyUserQueryScope($query, $actor);

        return $query
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, array $roleNames = [], array $storeIds = [], ?User $actor = null): User
    {
        if ($actor) {
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;
        }

        $data = $this->normalizeUserData($data, isCreate: true);

        $user = User::create($data);

        if ($roleNames) {
            $user->syncRoles($roleNames);
        }

        if ($storeIds) {
            $user->stores()->sync($storeIds);
        }

        $this->auditLogger->log('create', 'users', User::class, $user->id, null, $user->only(['id', 'name', 'email', 'company_id', 'status', 'base_type']));

        return $user->load(['roles', 'stores']);
    }

    public function update(User $user, array $data, ?array $roleNames = null, ?array $storeIds = null, ?User $actor = null): User
    {
        $old = $user->only(['id', 'name', 'email', 'company_id', 'status', 'default_store_id', 'base_type']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $data = $this->normalizeUserData($data, isCreate: false, user: $user);

        $user->update($data);

        if ($roleNames !== null) {
            $user->syncRoles($roleNames);
        }

        if ($storeIds !== null) {
            $user->stores()->sync($storeIds);
        }

        $this->auditLogger->log('update', 'users', User::class, $user->id, $old, $user->fresh()->toArray());

        return $user->fresh(['roles', 'stores']);
    }

    public function delete(User $user): void
    {
        $old = $user->toArray();
        $user->delete();

        $this->auditLogger->log('delete', 'users', User::class, $user->id, $old, null);
    }

    /** @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeUserData(array $data, bool $isCreate, ?User $user = null): array
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $suffix = trim((string) ($data['suffix'] ?? ''));
        $displayName = trim((string) ($data['display_name'] ?? ''));

        if ($displayName !== '') {
            $data['name'] = $displayName;
        } elseif ($firstName !== '' || $lastName !== '') {
            $parts = array_filter([$firstName, $middleName, $lastName, $suffix]);
            $data['name'] = trim(implode(' ', $parts));
        } elseif (empty($data['name'])) {
            $data['name'] = $user?->name ?? trim((string) ($data['email'] ?? 'User'));
        }

        if (! empty($data['password'])) {
            $data['password_changed_at'] = now();
        }

        $baseType = $data['base_type'] ?? 'store';

        if ($baseType === 'head_office') {
            $data['default_store_id'] = null;
            $data['default_warehouse_id'] = null;
        } elseif ($baseType === 'warehouse') {
            $data['default_store_id'] = null;
        } elseif ($baseType === 'store') {
            $data['default_warehouse_id'] = null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($data['is_active'] === false && ($data['status'] ?? 'active') === 'active') {
            $data['status'] = 'inactive';
        }

        if (($data['status'] ?? 'active') === 'inactive') {
            $data['is_active'] = false;
        }

        if (! ($data['is_locked'] ?? false)) {
            $data['locked_at'] = null;
            $data['failed_login_attempts'] = 0;
        }

        unset($data['password_confirmation']);

        return $data;
    }
}
