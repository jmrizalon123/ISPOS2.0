<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Organization\Services\SalesPlanService;
use App\Models\SalesPlan;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreSalesPlanRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateSalesPlanRequest;

class SalesPlanController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected SalesPlanService $salesPlanService)
    {
        $this->authorizeResource(SalesPlan::class, 'sales_plan');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/SalesPlans/Index', [
            'salesPlans' => $this->salesPlanService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
                $ctx['storeId'],
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/SalesPlans/Form', [
            'salesPlan' => null,
            'companies' => $this->companyOptions($request),
            'stores' => $this->assignableStores($request),
        ]);
    }

    public function store(StoreSalesPlanRequest $request): RedirectResponse
    {
        $this->salesPlanService->create($request->validated(), $request->user());

        return redirect()->route('admin.sales-plans.index')->with('success', 'Sales plan created.');
    }

    public function edit(Request $request, SalesPlan $salesPlan): Response
    {
        return Inertia::render('Admin/SalesPlans/Form', [
            'salesPlan' => $salesPlan->load([
                'company:id,name,company_code,display_name',
                'stores:id,store_name,store_code,sales_plan_id,company_id,store_category',
            ]),
            'companies' => $this->companyOptions($request),
            'stores' => $this->assignableStores($request),
        ]);
    }

    public function update(UpdateSalesPlanRequest $request, SalesPlan $salesPlan): RedirectResponse
    {
        $this->salesPlanService->update($salesPlan, $request->validated(), $request->user());

        return redirect()->route('admin.sales-plans.index')->with('success', 'Sales plan updated.');
    }

    public function destroy(SalesPlan $salesPlan): RedirectResponse
    {
        $this->salesPlanService->delete($salesPlan);

        return redirect()->route('admin.sales-plans.index')->with('success', 'Sales plan deleted.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Store>
     */
    protected function assignableStores(Request $request)
    {
        return Store::query()
            ->with('salesPlan:id,name,plan_code')
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id', 'sales_plan_id', 'store_category']);
    }
}
