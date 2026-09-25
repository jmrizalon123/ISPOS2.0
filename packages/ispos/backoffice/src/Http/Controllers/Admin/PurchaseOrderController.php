<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Purchasing\Services\PurchaseOrderQueryService;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StorePurchaseOrderRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
        protected PurchaseOrderQueryService $queryService,
    ) {
        $this->authorizeResource(PurchaseOrder::class, 'purchase_order');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('purchasing.view'), 403);

        $companyId = $request->string('company_id')->toString() ?: null;
        if (! $request->user()->hasGlobalOrganizationAccess()) {
            $companyId = $request->user()->company_id;
        }

        return Inertia::render('Admin/Purchasing/PurchaseOrders/Index', [
            'purchaseOrders' => $this->queryService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $companyId,
                $request->string('store_id')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $companyId ?? '',
                'store_id' => $request->string('store_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Purchasing/PurchaseOrders/Form', [
            'purchaseOrder' => null,
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'suppliers' => $this->supplierOptions($request),
            'products' => $this->trackedProductOptions($request),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $store = Store::query()->findOrFail($request->validated('store_id'));
        $supplier = Supplier::query()->findOrFail($request->validated('supplier_id'));
        abort_unless($request->user()->canAccessStore($store), 403);

        $po = $this->purchaseOrderService->create(
            $request->user(),
            $store,
            $supplier,
            $request->only(['order_date', 'expected_date', 'notes']),
            $request->validated('lines'),
        );

        return redirect()->route('admin.purchase-orders.edit', $po)->with('success', 'Purchase order created.');
    }

    public function edit(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['lines.product:id,sku,name', 'supplier', 'store', 'approver:id,name']);

        return Inertia::render('Admin/Purchasing/PurchaseOrders/Form', [
            'purchaseOrder' => $purchaseOrder,
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'suppliers' => $this->supplierOptions($request),
            'products' => $this->trackedProductOptions($request),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrderService->updateDraft(
            $purchaseOrder,
            $request->user(),
            $request->only(['store_id', 'supplier_id', 'order_date', 'expected_date', 'notes']),
            $request->validated('lines'),
        );

        return redirect()->route('admin.purchase-orders.edit', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrderService->delete($purchaseOrder, auth()->user());

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order deleted.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('approve', $purchaseOrder);
        $this->purchaseOrderService->approve($purchaseOrder, auth()->user());

        return redirect()->route('admin.purchase-orders.edit', $purchaseOrder)->with('success', 'Purchase order approved.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);
        $this->purchaseOrderService->cancel($purchaseOrder, auth()->user());

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order cancelled.');
    }

    /** @return list<array{id: string, name: string, supplier_code: string}> */
    protected function supplierOptions(Request $request): array
    {
        $query = Supplier::query()->where('status', 'active')->orderBy('name');

        if (! $request->user()->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query->get(['id', 'name', 'supplier_code'])->all();
    }

    /** @return list<array{id: string, sku: string, name: string}> */
    protected function trackedProductOptions(Request $request): array
    {
        $query = Product::query()
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->orderBy('name');

        if (! $request->user()->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query->get(['id', 'sku', 'name'])->all();
    }
}
