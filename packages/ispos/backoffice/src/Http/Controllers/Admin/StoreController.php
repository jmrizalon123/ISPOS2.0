<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Organization\Services\StoreLogoService;
use App\Domains\Organization\Services\StoreService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreStoreRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateStoreRequest;
use App\Models\Company;
use App\Models\SalesPlan;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
        protected StoreLogoService $logoService,
    ) {
        $this->authorizeResource(Store::class, 'store');
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Stores/Index', [
            'stores' => $this->storeService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $request->string('company_id')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            ),
            'companies' => $this->companyOptions($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $request->string('company_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Stores/Form', [
            'store' => null,
            'companies' => $this->companyOptions($request),
            'managers' => $this->managerOptions($request),
            'salesPlans' => $this->salesPlanOptions($request),
        ]);
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['logo'], $data['remove_logo']);

        if ($logo = $request->file('logo')) {
            $data['logo'] = $this->logoService->store($logo);
        }

        $this->storeService->create($data, $request->user());

        return redirect()->route('admin.stores.index')->with('success', 'Store created.');
    }

    public function edit(Request $request, Store $store): Response
    {
        return Inertia::render('Admin/Stores/Form', [
            'store' => $store->load('salesPlan:id,name,plan_code'),
            'companies' => $this->companyOptions($request),
            'managers' => $this->managerOptions($request),
            'salesPlans' => $this->salesPlanOptions($request),
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $data = $request->validated();
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        unset($data['logo'], $data['remove_logo']);

        if ($logo = $request->file('logo')) {
            $data['logo'] = $this->logoService->replace($store, $logo);
        } elseif ($removeLogo) {
            $this->logoService->forget($store);
            $data['logo'] = null;
        }

        $this->storeService->update($store, $data, $request->user());

        return redirect()->route('admin.stores.index')->with('success', 'Store updated.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->logoService->forget($store);
        $this->storeService->delete($store);

        return redirect()->route('admin.stores.index')->with('success', 'Store deleted.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Company>
     */
    private function companyOptions(Request $request)
    {
        return $request->user()->hasGlobalOrganizationAccess()
            ? Company::query()->orderBy('name')->get(['id', 'name', 'company_code', 'display_name'])
            : Company::query()->whereKey($request->user()->company_id)->get(['id', 'name', 'company_code', 'display_name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function managerOptions(Request $request)
    {
        return User::query()
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company_id']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SalesPlan>
     */
    private function salesPlanOptions(Request $request)
    {
        return SalesPlan::query()
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'plan_code', 'company_id', 'status']);
    }
}
