<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Identity\Services\RoleMemberService;
use App\Models\User;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\BulkRoleMemberRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleMemberController extends Controller
{
    public function __construct(protected RoleMemberService $roleMemberService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        abort_unless($request->user()?->can('roles.view'), 403);

        $roles = $this->roleMemberService->roleSummaries($request->user());
        $roleId = $request->integer('role_id') ?: ($roles[0]['id'] ?? null);
        $search = $request->string('search')->toString() ?: null;
        $role = $roleId ? Role::query()->find($roleId) : null;

        return Inertia::render('Admin/RoleMembers/Index', [
            'roles' => $roles,
            'selectedRoleId' => $roleId,
            'members' => $roleId
                ? $this->roleMemberService->listForRole($request->user(), $roleId, $search)
                : [],
            'availableUsers' => $role ? $this->roleMemberService->availableUsers($request->user(), $role) : [],
            'filters' => [
                'search' => $search ?? '',
                'role_id' => $roleId ? (string) $roleId : '',
            ],
        ]);
    }

    public function bulkStore(BulkRoleMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $assigned = $this->roleMemberService->assignMany(
            $request->user(),
            $data['role_id'],
            $data['user_ids'],
        );

        $message = $assigned === 1
            ? '1 user assigned to the role.'
            : "{$assigned} users assigned to the role.";

        return redirect()
            ->route('admin.users.role-members.index', ['role_id' => $data['role_id']])
            ->with('success', $message);
    }

    public function destroy(User $user, Role $role): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->roleMemberService->remove(request()->user(), $user, $role);

        return redirect()
            ->route('admin.users.role-members.index', ['role_id' => $role->id])
            ->with('success', 'Role assignment removed.');
    }
}
