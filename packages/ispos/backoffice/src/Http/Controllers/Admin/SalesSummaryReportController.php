<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Reporting\Services\SalesSummaryReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesSummaryReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(protected SalesSummaryReportService $summaryReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];
        $summaryFilters = array_merge($filters, ['status' => 'completed']);

        return Inertia::render('Admin/Reports/Sales/Summary', [
            'summary' => $this->summaryReportService->summary($request->user(), $summaryFilters),
            'trend' => $this->summaryReportService->dailyTrend($request->user(), $summaryFilters, 14),
            'storeBreakdown' => $this->summaryReportService->byStore($request->user(), $summaryFilters)->values(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $filters,
        ]);
    }
}
