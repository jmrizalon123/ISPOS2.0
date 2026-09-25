<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Inventory\Services\StoreInventoryQueryService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreInventoryController extends Controller
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

        $contextService = app(BackofficeContextService::class);
        $storeId = $request->string('store_id')->toString() ?: $contextService->effectiveStoreId($request->user());

        return Inertia::render('Admin/Inventory/Stock/Index', [
            'inventories' => $this->queryService->paginateInventory(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $companyId,
                $storeId,
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $companyId ?? '',
                'store_id' => $storeId ?? '',
            ],
        ]);
    }
}
