<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Crm\Services\LoyaltyProgramService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreLoyaltyProgramRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateLoyaltyProgramRequest;
use App\Models\LoyaltyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyProgramController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected LoyaltyProgramService $loyaltyProgramService)
    {
        $this->authorizeResource(LoyaltyProgram::class, 'loyalty_program');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Crm/LoyaltyPrograms/Index', [
            'loyaltyPrograms' => $this->loyaltyProgramService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Crm/LoyaltyPrograms/Form', [
            'loyaltyProgram' => null,
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function store(StoreLoyaltyProgramRequest $request): RedirectResponse
    {
        $this->loyaltyProgramService->create($request->validated(), $request->user());

        return redirect()->route('admin.loyalty-programs.index')->with('success', 'Loyalty program created.');
    }

    public function edit(Request $request, LoyaltyProgram $loyaltyProgram): Response
    {
        return Inertia::render('Admin/Crm/LoyaltyPrograms/Form', [
            'loyaltyProgram' => $loyaltyProgram->load('company:id,name'),
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $loyaltyProgram): RedirectResponse
    {
        $this->loyaltyProgramService->update($loyaltyProgram, $request->validated(), $request->user());

        return redirect()->route('admin.loyalty-programs.index')->with('success', 'Loyalty program updated.');
    }

    public function destroy(LoyaltyProgram $loyaltyProgram): RedirectResponse
    {
        $this->loyaltyProgramService->delete($loyaltyProgram);

        return redirect()->route('admin.loyalty-programs.index')->with('success', 'Loyalty program deleted.');
    }
}
