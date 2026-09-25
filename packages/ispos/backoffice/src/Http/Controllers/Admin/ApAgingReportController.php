<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\ApReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApAgingReportController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected ApReportService $apReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('ap.view'), 403);

        $companyId = $request->user()->hasGlobalOrganizationAccess()
            ? ($request->string('company_id')->toString() ?: null)
            : $request->user()->company_id;

        $asOfDate = $request->string('as_of_date')->toString() ?: now()->toDateString();

        $rows = $this->apReportService->aging($request->user(), $companyId, $asOfDate);
        $totals = $this->apReportService->agingTotals($rows);

        return Inertia::render('Admin/Accounting/Reports/ApAging', [
            'rows' => $rows->values(),
            'totals' => $totals,
            'companies' => $this->companyOptions($request),
            'filters' => [
                'company_id' => $companyId ?? '',
                'as_of_date' => $asOfDate,
            ],
        ]);
    }
}
