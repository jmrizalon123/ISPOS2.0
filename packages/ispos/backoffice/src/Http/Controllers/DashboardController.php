<?php

namespace Ispos\Backoffice\Http\Controllers;

use App\Domains\Inventory\Services\LowStockAlertService;
use App\Domains\Sales\Services\SaleQueryService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        LowStockAlertService $lowStockAlertService,
        SaleQueryService $saleQueryService,
    ): Response {
        $user = auth()->user();
        abort_unless($user?->can('dashboard.view'), 403);

        $canViewInventory = $user->can('inventory.view');
        $companyId = $user->hasGlobalOrganizationAccess() ? null : $user->company_id;
        $salesStats = $saleQueryService->todayStats($user);
        $canViewReports = $user->can('reports.view');

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_sales' => $salesStats['today_sales'],
                'transactions' => $salesStats['transactions'],
                'average_transaction' => $salesStats['average_transaction'],
                'gross_profit' => 0,
                'low_stock_count' => $canViewInventory ? $lowStockAlertService->count($user, $companyId) : 0,
            ],
            'salesTrend' => $canViewReports ? $saleQueryService->last14DayTrend($user) : [],
            'lowStockItems' => $canViewInventory
                ? $lowStockAlertService->list($user, $companyId, null, 5)->values()
                : [],
        ]);
    }
}
