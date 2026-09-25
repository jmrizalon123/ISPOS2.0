<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Organization\Services\CompanyService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreCompanyRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService)
    {
        $this->authorizeResource(Company::class, 'company');
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Companies/Index', [
            'companies' => $this->companyService->paginate($request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => null,
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = $this->companyService->create($request->validated(), $request->user());

        $user = $request->user();
        if ($user && ! $user->fresh()->company) {
            $user->update(['company_id' => $company->id]);
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company created.');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->companyService->update($company, $request->validated(), $request->user());

        return redirect()->route('admin.companies.index')->with('success', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->companyService->delete($company);

        return redirect()->route('admin.companies.index')->with('success', 'Company deleted.');
    }
}
