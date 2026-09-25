<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Inventory\Services\StoreInventoryQueryService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected StoreInventoryQueryService $queryService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $companyId = $request->string('company_id')->toString() ?: null;
        if (! $request->user()->hasGlobalOrganizationAccess()) {
            $companyId = $request->user()->company_id;
        }

        $storeId = $request->string('store_id')->toString() ?: null;

        return Inertia::render('Admin/Inventory/Movements/Index', [
            'movements' => $this->queryService->paginateMovements(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $companyId,
                $storeId,
                $request->string('movement_type')->toString() ?: null,
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'movementTypes' => ['sale', 'void', 'adjustment', 'opening_balance', 'recipe_consumption', 'component_consumption', 'purchase_receipt', 'purchase_return'],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $companyId ?? '',
                'store_id' => $storeId ?? '',
                'movement_type' => $request->string('movement_type')->toString(),
            ],
        ]);
    }
}
