<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use Ispos\Backoffice\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $this->authorize('roles.view');

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount(['users', 'permissions'])
            ->when(! $request->user()?->isSuperAdmin(), fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->when(! $request->user()?->isDeveloper(), fn ($query) => $query->where('name', '!=', 'Developer'))
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        $firstRole = $roles->first();
        $roleId = $request->integer('role_id') ?: ($firstRole['id'] ?? null);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'selectedRoleId' => $roleId,
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
            'filters' => [
                'role_id' => $roleId ? (string) $roleId : '',
            ],
            'canUpdate' => $request->user()?->can('roles.update') ?? false,
            'canDelete' => $request->user()?->can('roles.delete') ?? false,
            'canCreate' => $request->user()?->can('roles.create') ?? false,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('roles.create');

        return Inertia::render('Admin/Roles/Form', [
            'role' => null,
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:125', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        $this->auditLogger->log('create', 'roles', Role::class, (string) $role->id, null, [
            'name' => $role->name,
            'permissions' => $data['permissions'] ?? [],
        ]);

        return redirect()
            ->route('admin.roles.index', ['role_id' => $role->id])
            ->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        $this->authorize('roles.update');

        return Inertia::render('Admin/Roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.update');

        if ($role->name === 'Super Admin' && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($role->name === 'Developer' && ! $request->user()->isDeveloper()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:125', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $old = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];

        if (! in_array($role->name, ['Super Admin', 'Developer'], true)) {
            $role->name = $data['name'];
            $role->save();
        }

        $role->syncPermissions($data['permissions'] ?? []);

        $this->auditLogger->log('update', 'roles', Role::class, (string) $role->id, $old, [
            'name' => $role->name,
            'permissions' => $data['permissions'] ?? [],
        ]);

        return redirect()
            ->route('admin.roles.index', ['role_id' => $role->id])
            ->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.delete');

        if (in_array($role->name, ['Super Admin', 'Developer', 'Company Admin', 'Cashier'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $old = ['name' => $role->name];
        $role->delete();

        $this->auditLogger->log('delete', 'roles', Role::class, (string) $role->id, $old, null);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted.');
    }
}
