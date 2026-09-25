<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Identity\Services\UserAvatarService;
use App\Domains\Identity\Services\UserService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreUserRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected UserAvatarService $avatarService,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => $this->userService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
            ),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $this->sanitizeRoles($request, $data['roles'] ?? [], null);
        $storeIds = $data['store_ids'] ?? [];
        unset($data['roles'], $data['store_ids'], $data['avatar'], $data['remove_avatar']);

        if ($avatar = $request->file('avatar')) {
            $data['avatar'] = $this->avatarService->store($avatar);
        }

        $this->userService->create($data, $roles, $storeIds, $request->user());

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(Request $request, User $user): Response
    {
        $user->load(['roles:id,name', 'stores:id', 'creator:id,name', 'updater:id,name']);

        return Inertia::render('Admin/Users/Form', [
            'user' => [
                ...$user->only([
                    'id',
                    'uuid',
                    'employee_id',
                    'username',
                    'email',
                    'phone',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'display_name',
                    'avatar',
                    'avatar_url',
                    'name',
                    'company_id',
                    'department_id',
                    'position_id',
                    'base_type',
                    'default_store_id',
                    'default_warehouse_id',
                    'status',
                    'is_active',
                    'is_locked',
                    'failed_login_attempts',
                    'language',
                    'timezone',
                    'email_verified_at',
                    'password_changed_at',
                    'two_factor_enabled',
                    'locked_at',
                    'last_login_at',
                    'last_login_ip',
                    'last_activity_at',
                    'created_at',
                    'updated_at',
                ]),
                'roles' => $user->roles->pluck('name'),
                'store_ids' => $user->stores->pluck('id'),
                'creator' => $user->creator,
                'updater' => $user->updater,
            ],
            ...$this->formOptions($request),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roles = $this->sanitizeRoles($request, $data['roles'] ?? [], $user);
        $storeIds = $data['store_ids'] ?? [];
        $removeAvatar = (bool) ($data['remove_avatar'] ?? false);
        unset($data['roles'], $data['store_ids'], $data['avatar'], $data['remove_avatar']);

        if ($avatar = $request->file('avatar')) {
            $data['avatar'] = $this->avatarService->replace($user, $avatar);
        } elseif ($removeAvatar) {
            $this->avatarService->forget($user);
            $data['avatar'] = null;
        }

        $this->userService->update($user, $data, $roles, $storeIds, $request->user());

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    protected function formOptions(Request $request): array
    {
        $contextService = app(BackofficeContextService::class);
        $companyScope = fn ($query) => $query->when(
            ! $request->user()->hasGlobalOrganizationAccess(),
            fn ($q) => $q->where('company_id', $request->user()->company_id),
        );

        $storeQuery = $contextService->applyStoreQueryScope(Store::query(), $request->user());

        return [
            'companies' => $request->user()->hasGlobalOrganizationAccess()
                ? Company::query()->orderBy('name')->get(['id', 'name', 'company_code', 'display_name'])
                : Company::query()->whereKey($request->user()->company_id)->get(['id', 'name', 'company_code', 'display_name']),
            'stores' => $storeQuery
                ->where(function ($query) {
                    $query->whereNull('store_type')
                        ->orWhereNotIn('store_type', ['warehouse']);
                })
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code', 'company_id', 'store_type']),
            'warehouses' => $contextService->applyStoreQueryScope(Store::query(), $request->user())
                ->where('store_type', 'warehouse')
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code', 'company_id', 'store_type']),
            'departments' => $companyScope(Department::query())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'company_id']),
            'positions' => $companyScope(Position::query())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'company_id', 'department_id']),
            'roles' => Role::query()
                ->when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('name', '!=', 'Super Admin'))
                ->when(! $request->user()->isDeveloper(), fn ($q) => $q->where('name', '!=', 'Developer'))
                ->orderBy('name')
                ->pluck('name'),
        ];
    }

    /** @param  list<string>  $roles
     * @return list<string>
     */
    protected function sanitizeRoles(Request $request, array $roles, ?User $targetUser = null): array
    {
        $sanitized = $roles;

        if (! $request->user()->isSuperAdmin()) {
            $sanitized = array_values(array_diff($sanitized, ['Super Admin']));
        }

        if (! $request->user()->isDeveloper()) {
            $sanitized = array_values(array_diff($sanitized, ['Developer']));

            if ($targetUser?->hasRole('Developer')) {
                $sanitized[] = 'Developer';
                $sanitized = array_values(array_unique($sanitized));
            }
        }

        return $sanitized;
    }
}
