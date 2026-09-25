<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Inventory\Services\LowStockAlertService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LowStockAlertController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected LowStockAlertService $lowStockAlertService)
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

        $query = $this->lowStockAlertService->baseQuery($request->user(), $companyId, $storeId)
            ->with([
                'store:id,store_name,store_code',
                'product:id,sku,name,warning_qty,ideal_qty,track_inventory,status,category_id,unit_id,company_id',
                'product.category:id,name,category_code',
                'product.unit:id,name,symbol',
                'product.company:id,name,company_code,display_name',
            ]);

        if ($search = $request->string('search')->toString()) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Inventory/LowStock', [
            'inventories' => $query->orderBy('qty')->paginate(15)->withQueryString(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $companyId ?? '',
                'store_id' => $storeId ?? '',
            ],
            'summary' => [
                'total' => $this->lowStockAlertService->count($request->user(), $companyId, $storeId),
            ],
        ]);
    }
}
