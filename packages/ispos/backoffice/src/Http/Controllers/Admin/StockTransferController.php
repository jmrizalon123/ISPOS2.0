<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Inventory\Services\StockTransferQueryService;
use App\Domains\Inventory\Services\StockTransferService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreStockTransferRequest;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    use ProvidesCompanyOptions;

    public function __construct(
        protected StockTransferService $transferService,
        protected StockTransferQueryService $queryService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockTransfer::class);

        $filters = [
            'company_id' => $request->user()->hasGlobalOrganizationAccess()
                ? ($request->string('company_id')->toString() ?: null)
                : $request->user()->company_id,
            'store_id' => $request->string('store_id')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        return Inertia::render('Admin/Inventory/Transfers/Index', [
            'transfers' => $this->queryService->paginate($request->user(), $filters),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => [
                'company_id' => $filters['company_id'] ?? '',
                'store_id' => $filters['store_id'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'canCreate' => $request->user()->can('inventory.adjust'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', StockTransfer::class);

        $companyIds = $this->accessibleCompanyIds($request->user());

        $products = Product::query()
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->when($companyIds !== null, fn ($q) => $q->whereIn('company_id', $companyIds))
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'company_id', 'image']);

        $barcodesByProduct = ProductBarcode::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->get(['product_id', 'barcode'])
            ->groupBy('product_id');

        $products = $products
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'company_id' => $product->company_id,
                'image' => $product->image,
                'barcodes' => $barcodesByProduct->get($product->id)?->pluck('barcode')->values()->all() ?? [],
            ])
            ->values();

        $inventories = StoreProductInventory::query()
            ->when($companyIds !== null, fn ($q) => $q->whereIn('company_id', $companyIds))
            ->get(['store_id', 'product_id', 'qty'])
            ->map(fn (StoreProductInventory $row) => [
                'store_id' => $row->store_id,
                'product_id' => $row->product_id,
                'qty' => (string) $row->qty,
            ])
            ->values();

        $locations = $this->transferStoreOptions($request);
        $warehouses = $locations->where('store_type', 'warehouse')->values();
        $branchStores = $locations->where('store_type', '!=', 'warehouse')->values();
        if ($warehouses->isEmpty()) {
            $warehouses = $locations->values();
        }
        if ($branchStores->isEmpty()) {
            $branchStores = $locations->values();
        }

        return Inertia::render('Admin/Inventory/Transfers/Form', [
            'stores' => $branchStores,
            'warehouses' => $warehouses,
            'products' => $products,
            'inventories' => $inventories,
            'transferTypes' => StockTransfer::TYPES,
            'priorities' => StockTransfer::PRIORITIES,
        ]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $fromStore = Store::query()->findOrFail($request->sourceLocationId());
        $toStore = Store::query()->findOrFail($request->destinationLocationId());

        abort_unless($request->user()->canAccessStore($fromStore), 403);
        abort_unless($request->user()->canAccessStore($toStore), 403);

        $validated = $request->validated();

        $transfer = $this->transferService->createAndComplete(
            $request->user(),
            $fromStore,
            $toStore,
            $validated['lines'],
            $validated['notes'] ?? null,
            [
                'transfer_type' => $validated['transfer_type'],
                'priority' => $validated['priority'],
                'reason' => $validated['reason'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'transfer_date' => $validated['transfer_date'] ?? null,
                'requested_date' => $validated['requested_date'] ?? null,
                'expected_date' => $validated['expected_date'] ?? null,
                'from_warehouse_id' => $validated['from_warehouse_id'] ?? null,
                'to_warehouse_id' => $validated['to_warehouse_id'] ?? null,
            ],
        );

        return redirect()
            ->route('admin.inventory.transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_no} received.");
    }

    public function show(StockTransfer $transfer): Response
    {
        $this->authorize('view', $transfer);

        return Inertia::render('Admin/Inventory/Transfers/Show', [
            'transfer' => $this->queryService->detail($transfer),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Store>
     */
    protected function transferStoreOptions(Request $request)
    {
        $user = $request->user();

        return Store::query()
            ->when(! $user->hasGlobalOrganizationAccess(), function ($q) use ($user) {
                if ($user->hasRole('Company Admin') && $user->company_id) {
                    $q->where('company_id', $user->company_id);

                    return;
                }

                $storeIds = $user->stores()->pluck('stores.id');

                if ($user->company_id) {
                    $q->where(function ($inner) use ($user, $storeIds) {
                        $inner->where('company_id', $user->company_id)
                            ->orWhereIn('id', $storeIds);
                    });
                } else {
                    $q->whereIn('id', $storeIds);
                }
            })
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id', 'store_type']);
    }

    /**
     * Company IDs the user may transfer catalog products for.
     * Null means every company (global organization access).
     *
     * @return list<string>|null
     */
    protected function accessibleCompanyIds(User $user): ?array
    {
        if ($user->hasGlobalOrganizationAccess()) {
            return null;
        }

        $ids = Collection::make([$user->company_id])
            ->merge($user->stores()->pluck('stores.company_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $ids;
    }
}
