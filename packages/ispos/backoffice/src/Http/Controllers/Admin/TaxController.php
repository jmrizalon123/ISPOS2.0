<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Catalog\Services\TaxService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreTaxRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateTaxRequest;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected TaxService $taxService)
    {
        $this->authorizeResource(Tax::class, 'tax');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Taxes/Index', [
            'taxes' => $this->taxService->paginate(
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
        return Inertia::render('Admin/Taxes/Form', [
            'tax' => null,
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function store(StoreTaxRequest $request): RedirectResponse
    {
        $this->taxService->create($request->validated(), $request->user());

        return redirect()->route('admin.taxes.index')->with('success', 'Tax created.');
    }

    public function edit(Request $request, Tax $tax): Response
    {
        return Inertia::render('Admin/Taxes/Form', [
            'tax' => $tax->load('company:id,name'),
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function update(UpdateTaxRequest $request, Tax $tax): RedirectResponse
    {
        $this->taxService->update($tax, $request->validated(), $request->user());

        return redirect()->route('admin.taxes.index')->with('success', 'Tax updated.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $this->taxService->delete($tax);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax deleted.');
    }
}
