<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Reporting\Services\SalesRegisterReportService;
use App\Domains\Reporting\Services\SalesReportExportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesRegisterReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(
        protected SalesRegisterReportService $registerReportService,
        protected SalesReportExportService $exportService,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];
        if (! $request->has('status')) {
            $filters['status'] = 'all';
        }

        $perPage = match ($request->integer('per_page', 20)) {
            10, 50 => $request->integer('per_page'),
            default => 20,
        };

        return Inertia::render('Admin/Reports/Sales/Register', [
            'sales' => $this->registerReportService->paginate(
                $request->user(),
                $filters,
                $request->string('search')->toString() ?: null,
                $perPage,
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => array_merge($filters, [
                'search' => $request->string('search')->toString(),
            ]),
            'canExport' => $request->user()->can('reports.export'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];
        if (! $request->has('status')) {
            $filters['status'] = 'all';
        }

        return $this->exportService->registerCsv($request->user(), $filters);
    }
}
