<?php

namespace Ispos\Backoffice\Http\Controllers\Kds;

use App\Domains\Kds\Services\KdsContextService;
use App\Domains\Kds\Services\KitchenTicketQueryService;
use App\Domains\Kds\Services\KitchenTicketService;
use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\KitchenTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KdsTicketController extends Controller
{
    public function index(Request $request, KitchenTicketQueryService $queryService): JsonResponse
    {
        $this->authorize('viewAny', KitchenTicket::class);

        $store = app(KdsContextService::class)->resolveStore($request->user());
        abort_unless($store, 403, 'KDS store not configured.');

        return response()->json([
            'tickets' => $queryService->activeForStore($store)->values(),
        ]);
    }

    public function update(
        Request $request,
        KitchenTicket $kitchenTicket,
        KitchenTicketService $ticketService,
        KitchenTicketQueryService $queryService,
    ): RedirectResponse|JsonResponse {
        $this->authorize('update', $kitchenTicket);

        $validated = $request->validate([
            'status' => ['required', 'in:preparing,ready,completed'],
        ]);

        $ticketService->advanceStatus($request->user(), $kitchenTicket, $validated['status']);

        if ($request->wantsJson()) {
            $store = $kitchenTicket->store;

            return response()->json([
                'ticket' => $queryService->serialize($kitchenTicket->fresh([
                    'sale.register', 'sale.user', 'sale.lines.product', 'sale.lines.modifiers', 'sale.lines.components',
                ])),
                'tickets' => $queryService->activeForStore($store)->values(),
            ]);
        }

        return back();
    }
}
