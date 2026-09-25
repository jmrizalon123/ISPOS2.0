<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Reporting\Services\ProductSalesReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesReportFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductSalesReportController extends Controller
{
    use ProvidesCompanyOptions, ResolvesReportFilters;

    public function __construct(protected ProductSalesReportService $productSalesReportService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $ctx = $this->resolveReportFilters($request);
        $filters = array_merge($ctx['filters'], ['status' => 'completed']);

        return Inertia::render('Admin/Reports/Sales/Products', [
            'products' => $this->productSalesReportService
                ->topProducts($request->user(), $filters)
                ->values(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }
}
