<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\FinancialStatementReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitAndLossReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(protected FinancialStatementReportService $financialStatementReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('accounting.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];

        $report = $this->financialStatementReportService->profitAndLoss(
            $request->user(),
            $filters['company_id'] ?: null,
            $filters['date_from'],
            $filters['date_to'],
        );

        return Inertia::render('Admin/Accounting/Reports/ProfitAndLoss', [
            'revenue' => $report['revenue']->values(),
            'expenses' => $report['expenses']->values(),
            'totals' => $report['totals'],
            'companies' => $this->companyOptions($request),
            'filters' => $filters,
        ]);
    }
}
