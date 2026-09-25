<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Catalog\Services\UnitService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreUnitRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected UnitService $unitService)
    {
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Units/Index', [
            'units' => $this->unitService->paginate(
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
        return Inertia::render('Admin/Units/Form', [
            'unit' => null,
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->unitService->create($request->validated(), $request->user());

        return redirect()->route('admin.units.index')->with('success', 'Unit created.');
    }

    public function edit(Request $request, Unit $unit): Response
    {
        return Inertia::render('Admin/Units/Form', [
            'unit' => $unit->load('company:id,name'),
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated(), $request->user());

        return redirect()->route('admin.units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->delete($unit);

        return redirect()->route('admin.units.index')->with('success', 'Unit deleted.');
    }
}
