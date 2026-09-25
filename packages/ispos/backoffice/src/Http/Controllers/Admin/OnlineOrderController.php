<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\OnlineStore\Services\OnlineOrderService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnlineOrderController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected OnlineOrderService $orders)
    {
        $this->authorizeResource(OnlineOrder::class, 'online_order');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/OnlineStore/Orders/Index', [
            'orders' => $this->orders->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['storeId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function show(OnlineOrder $onlineOrder): Response
    {
        $onlineOrder->load([
            'store:id,store_name,store_code,company_id',
            'company:id,name,display_name',
            'lines.modifiers',
            'acceptedBy:id,name',
            'rejectedBy:id,name',
        ]);

        return Inertia::render('Admin/OnlineStore/Orders/Show', [
            'order' => $onlineOrder,
        ]);
    }

    public function accept(Request $request, OnlineOrder $onlineOrder): RedirectResponse
    {
        $this->authorize('accept', $onlineOrder);
        $this->orders->updateStatus($onlineOrder, 'accepted', $request->user());

        return back()->with('success', 'Order accepted.');
    }

    public function reject(Request $request, OnlineOrder $onlineOrder): RedirectResponse
    {
        $this->authorize('reject', $onlineOrder);
        $reason = $request->string('rejection_reason')->toString() ?: null;
        $this->orders->updateStatus($onlineOrder, 'rejected', $request->user(), $reason);

        return back()->with('success', 'Order rejected.');
    }

    public function markReady(Request $request, OnlineOrder $onlineOrder): RedirectResponse
    {
        $this->authorize('markReady', $onlineOrder);
        $this->orders->updateStatus($onlineOrder, 'ready', $request->user());

        return back()->with('success', 'Order marked ready.');
    }

    public function complete(Request $request, OnlineOrder $onlineOrder): RedirectResponse
    {
        $this->authorize('complete', $onlineOrder);
        $this->orders->updateStatus($onlineOrder, 'completed', $request->user());

        return back()->with('success', 'Order completed.');
    }

    public function cancel(Request $request, OnlineOrder $onlineOrder): RedirectResponse
    {
        $this->authorize('cancel', $onlineOrder);
        $this->orders->updateStatus($onlineOrder, 'cancelled', $request->user());

        return back()->with('success', 'Order cancelled.');
    }
}
