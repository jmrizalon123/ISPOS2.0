<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\ChartOfAccountService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreChartOfAccountRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateChartOfAccountRequest;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected ChartOfAccountService $chartOfAccountService)
    {
        $this->authorizeResource(ChartOfAccount::class, 'chart_of_account');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Accounting/ChartOfAccounts/Index', [
            'accounts' => $this->chartOfAccountService->paginate(
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
        return Inertia::render('Admin/Accounting/ChartOfAccounts/Form', [
            'account' => null,
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $this->chartOfAccountService->create($request->validated(), $request->user());

        return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Account created.');
    }

    public function edit(Request $request, ChartOfAccount $chartOfAccount): Response
    {
        return Inertia::render('Admin/Accounting/ChartOfAccounts/Form', [
            'account' => $chartOfAccount->load('company:id,name'),
            'companies' => $this->companyOptions($request),
        ]);
    }

    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $this->chartOfAccountService->update($chartOfAccount, $request->validated(), $request->user());

        return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Account updated.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $this->chartOfAccountService->delete($chartOfAccount);

        return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Account deleted.');
    }
}
