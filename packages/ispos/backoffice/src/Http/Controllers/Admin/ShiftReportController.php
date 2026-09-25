<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Reporting\Services\PosShiftReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\PosShift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(protected PosShiftReportService $shiftReportService) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = $ctx['filters'];
        if (! $request->has('status')) {
            $filters['status'] = 'all';
        }

        return Inertia::render('Admin/Reports/Shifts/Index', [
            'shifts' => $this->shiftReportService->paginate(
                $request->user(),
                $filters,
                $request->string('search')->toString() ?: null,
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => array_merge($filters, [
                'search' => $request->string('search')->toString(),
            ]),
        ]);
    }

    public function show(Request $request, PosShift $posShift): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);
        abort_unless($this->shiftReportService->canViewShift($request->user(), $posShift), 403);

        return Inertia::render('Admin/Reports/Shifts/Show', [
            'report' => $this->shiftReportService->detail($request->user(), $posShift),
        ]);
    }
}
