<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Inventory\Services\InventoryAdjustmentService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreInventoryAdjustmentRequest;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryAdjustmentController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected InventoryAdjustmentService $adjustmentService)
    {
    }

    public function create(Request $request): Response
    {
        $this->authorize('adjust', \App\Models\StockMovement::class);

        $companyId = $this->resolveCompanyId($request);

        $products = Product::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        return Inertia::render('Admin/Inventory/Adjustments/Form', [
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'products' => $products,
        ]);
    }

    public function store(StoreInventoryAdjustmentRequest $request): RedirectResponse
    {
        $this->authorize('adjust', \App\Models\StockMovement::class);

        $store = Store::query()->findOrFail($request->validated('store_id'));
        $product = Product::query()->findOrFail($request->validated('product_id'));

        abort_unless($request->user()->canAccessStore($store), 403);

        $this->adjustmentService->adjust(
            $request->user(),
            $store,
            $product,
            (float) $request->validated('quantity_delta'),
            $request->validated('reason'),
            $request->validated('notes'),
        );

        return redirect()
            ->route('admin.inventory.movements.index', ['store_id' => $store->id])
            ->with('success', 'Stock adjustment recorded.');
    }
}
