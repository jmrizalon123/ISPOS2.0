<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Catalog\Services\PriceGroupService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StorePriceGroupRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdatePriceGroupRequest;
use App\Models\PriceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceGroupController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected PriceGroupService $priceGroupService)
    {
        $this->authorizeResource(PriceGroup::class, 'price_group');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/PriceGroups/Index', [
            'priceGroups' => $this->priceGroupService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/PriceGroups/Form', [
            'priceGroup' => null,
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function store(StorePriceGroupRequest $request): RedirectResponse
    {
        $this->priceGroupService->create($request->validated(), $request->user());

        return redirect()->route('admin.price-groups.index')->with('success', 'Price group created.');
    }

    public function edit(Request $request, PriceGroup $priceGroup): Response
    {
        return Inertia::render('Admin/PriceGroups/Form', [
            'priceGroup' => $priceGroup->load('company:id,name'),
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function update(UpdatePriceGroupRequest $request, PriceGroup $priceGroup): RedirectResponse
    {
        $this->priceGroupService->update($priceGroup, $request->validated(), $request->user());

        return redirect()->route('admin.price-groups.index')->with('success', 'Price group updated.');
    }

    public function destroy(PriceGroup $priceGroup): RedirectResponse
    {
        $this->priceGroupService->delete($priceGroup);

        return redirect()->route('admin.price-groups.index')->with('success', 'Price group deleted.');
    }
}
