<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Purchasing\Services\PurchaseReceivingService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\ReceivePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReceivingController extends Controller
{
    public function __construct(protected PurchaseReceivingService $receivingService)
    {
    }

    public function create(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('receive', $purchaseOrder);

        $purchaseOrder->load(['lines.product:id,sku,name', 'supplier', 'store']);

        return Inertia::render('Admin/Purchasing/PurchaseOrders/Receive', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function store(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->receivingService->receive(
            $purchaseOrder,
            $request->user(),
            $request->validated('receipts'),
        );

        return redirect()
            ->route('admin.purchase-orders.index')
            ->with('success', 'Goods received and inventory updated.');
    }
}
