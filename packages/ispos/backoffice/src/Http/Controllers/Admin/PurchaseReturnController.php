<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Purchasing\Services\PurchaseReturnService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StorePurchaseReturnRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdatePurchaseReturnRequest;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(protected PurchaseReturnService $returnService)
    {
        $this->authorizeResource(PurchaseReturn::class, 'purchase_return');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('purchasing.view'), 403);

        $companyId = $request->string('company_id')->toString() ?: null;
        if (! $request->user()->hasGlobalOrganizationAccess()) {
            $companyId = $request->user()->company_id;
        }

        $query = PurchaseReturn::query()
            ->with(['supplier:id,name,supplier_code', 'store:id,store_name,store_code', 'creator:id,name'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $request->user()->hasGlobalOrganizationAccess() && ! $request->user()->hasRole('Company Admin'), function ($q) use ($request) {
                $q->whereIn('store_id', $request->user()->stores()->pluck('stores.id'));
            })
            ->when($request->string('store_id')->toString(), fn ($q) => $q->where('store_id', $request->string('store_id')->toString()))
            ->when($request->string('status')->toString(), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('created_at');

        return Inertia::render('Admin/Purchasing/PurchaseReturns/Index', [
            'purchaseReturns' => $query->paginate(15)->withQueryString(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => [
                'company_id' => $companyId ?? '',
                'store_id' => $request->string('store_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Purchasing/PurchaseReturns/Form', [
            'purchaseReturn' => null,
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'suppliers' => $this->supplierOptions($request),
            'products' => $this->trackedProductOptions($request),
        ]);
    }

    public function store(StorePurchaseReturnRequest $request): RedirectResponse
    {
        $store = Store::query()->findOrFail($request->validated('store_id'));
        $supplier = Supplier::query()->findOrFail($request->validated('supplier_id'));
        abort_unless($request->user()->canAccessStore($store), 403);

        $return = $this->returnService->create(
            $request->user(),
            $store,
            $supplier,
            $request->only(['purchase_order_id', 'reason', 'notes']),
            $request->validated('lines'),
        );

        return redirect()->route('admin.purchase-returns.edit', $return)->with('success', 'Purchase return created.');
    }

    public function edit(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load(['lines.product:id,sku,name', 'supplier', 'store', 'purchaseOrder:id,po_number']);

        return Inertia::render('Admin/Purchasing/PurchaseReturns/Form', [
            'purchaseReturn' => $purchaseReturn,
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'suppliers' => $this->supplierOptions($request),
            'products' => $this->trackedProductOptions($request),
        ]);
    }

    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->returnService->updateDraft(
            $purchaseReturn,
            $request->user(),
            $request->only(['purchase_order_id', 'reason', 'notes']),
            $request->validated('lines'),
        );

        return redirect()->route('admin.purchase-returns.edit', $purchaseReturn)->with('success', 'Purchase return updated.');
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->returnService->delete($purchaseReturn, auth()->user());

        return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return deleted.');
    }

    public function post(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->authorize('post', $purchaseReturn);
        $this->returnService->post($purchaseReturn, auth()->user());

        return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return posted.');
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
