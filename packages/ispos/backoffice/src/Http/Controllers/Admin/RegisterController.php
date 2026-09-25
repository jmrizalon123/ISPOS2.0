<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Organization\Services\RegisterService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreRegisterRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateRegisterRequest;
use App\Models\PosDevice;
use App\Models\Register;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(protected RegisterService $registerService)
    {
        $this->authorizeResource(Register::class, 'register');
    }

    public function index(Request $request): Response
    {
        $contextService = app(BackofficeContextService::class);
        $contextStoreId = $contextService->effectiveStoreId($request->user());
        $storeId = $request->string('store_id')->toString() ?: $contextStoreId;

        return Inertia::render('Admin/Registers/Index', [
            'registers' => $this->registerService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $storeId,
                $request->string('status')->toString() ?: null,
            ),
            'stores' => $this->availableStores($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'store_id' => $storeId ?? '',
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureRegisterManagementScope($request);

        return Inertia::render('Admin/Registers/Form', $this->formPayload($request));
    }

    public function store(StoreRegisterRequest $request): RedirectResponse
    {
        $this->ensureRegisterManagementScope($request);

        $store = Store::query()->findOrFail($request->validated('store_id'));
        $this->authorize('view', $store);

        $this->registerService->create($request->validated(), $request->user());

        return redirect()->route('admin.registers.index')->with('success', 'Register created.');
    }

    public function edit(Request $request, Register $register): Response
    {
        $this->ensureRegisterManagementScope($request);

        return Inertia::render('Admin/Registers/Form', $this->formPayload($request, $register));
    }

    public function update(UpdateRegisterRequest $request, Register $register): RedirectResponse
    {
        $this->ensureRegisterManagementScope($request);

        $this->registerService->update($register, $request->validated(), $request->user());

        return redirect()->route('admin.registers.index')->with('success', 'Register updated.');
    }

    public function destroy(Register $register): RedirectResponse
    {
        $this->registerService->delete($register);

        return redirect()->route('admin.registers.index')->with('success', 'Register deleted.');
    }

    protected function ensureRegisterManagementScope(Request $request): void
    {
        if (app(BackofficeContextService::class)->isStoreScope()) {
            abort(403, 'Register management is only available in company scope.');
        }
    }

    /** @return array<string, mixed> */
    protected function formPayload(Request $request, ?Register $register = null): array
    {
        $stores = $this->availableStores($request);

        return [
            'register' => $register?->load([
                'store:id,store_name,store_code,company_id',
                'company:id,name,company_code,display_name',
                'device:id,name',
                'currentCashier:id,name',
                'creator:id,name',
                'updater:id,name',
            ]),
            'stores' => $stores,
            'posDevices' => PosDevice::query()
                ->when($stores->isNotEmpty(), fn ($q) => $q->whereIn('store_id', $stores->pluck('id')))
                ->orderBy('name')
                ->get(['id', 'name', 'store_id', 'register_id']),
        ];
    }

    protected function availableStores(Request $request)
    {
        $user = $request->user();
        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);

        return Store::query()
            ->when($contextStoreId, fn ($q) => $q->whereKey($contextStoreId))
            ->when(! $contextStoreId && ! $user->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $user->company_id))
            ->when(
                ! $contextStoreId
                    && ! $user->hasGlobalOrganizationAccess()
                    && ! $user->hasRole('Company Admin')
                    && ! $user->isHeadOfficeBased(),
                fn ($q) => $q->whereIn('id', $user->stores()->pluck('stores.id')),
            )
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id']);
    }
}
