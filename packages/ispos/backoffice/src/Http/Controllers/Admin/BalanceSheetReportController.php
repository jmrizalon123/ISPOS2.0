<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\FinancialStatementReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BalanceSheetReportController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected FinancialStatementReportService $financialStatementReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('accounting.view'), 403);

        $companyId = $request->user()->hasGlobalOrganizationAccess()
            ? ($request->string('company_id')->toString() ?: null)
            : $request->user()->company_id;

        $asOfDate = $request->string('as_of_date')->toString() ?: now()->toDateString();

        $report = $this->financialStatementReportService->balanceSheet(
            $request->user(),
            $companyId,
            $asOfDate,
        );

        return Inertia::render('Admin/Accounting/Reports/BalanceSheet', [
            'assets' => $report['assets']->values(),
            'liabilities' => $report['liabilities']->values(),
            'equity' => $report['equity']->values(),
            'netIncome' => $report['net_income'],
            'totals' => $report['totals'],
            'companies' => $this->companyOptions($request),
            'filters' => [
                'company_id' => $companyId ?? '',
                'as_of_date' => $asOfDate,
            ],
        ]);
    }
}
