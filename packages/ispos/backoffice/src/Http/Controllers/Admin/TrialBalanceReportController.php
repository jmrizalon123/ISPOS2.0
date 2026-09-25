<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\TrialBalanceReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrialBalanceReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(protected TrialBalanceReportService $trialBalanceReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('accounting.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];

        $rows = $this->trialBalanceReportService->trialBalance(
            $request->user(),
            $filters['company_id'] ?: null,
            $filters['date_from'],
            $filters['date_to'],
        );

        $totalDebits = $rows->reduce(fn ($carry, $row) => bcadd($carry, $row->debit_total, 4), '0');
        $totalCredits = $rows->reduce(fn ($carry, $row) => bcadd($carry, $row->credit_total, 4), '0');

        return Inertia::render('Admin/Accounting/Reports/TrialBalance', [
            'rows' => $rows->values(),
            'totals' => [
                'debits' => $totalDebits,
                'credits' => $totalCredits,
            ],
            'companies' => $this->companyOptions($request),
            'filters' => $filters,
        ]);
    }
}
