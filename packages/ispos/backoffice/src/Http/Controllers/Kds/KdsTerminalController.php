<?php

namespace Ispos\Backoffice\Http\Controllers\Kds;

use App\Domains\Kds\Services\KdsContextService;
use App\Domains\Kds\Services\KitchenTicketQueryService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KdsTerminalController extends Controller
{
    public function index(
        Request $request,
        KdsContextService $contextService,
        KitchenTicketQueryService $queryService,
    ): Response {
        $user = $request->user();
        abort_unless($user?->can('kds.view'), 403);

        $store = $contextService->resolveStore($user);

        if (! $store) {
            return Inertia::render('KDS/Setup', [
                'stores' => $contextService->accessibleStores($user),
                'context' => [
                    'store_id' => $contextService->getStoreId(),
                ],
            ]);
        }

        return Inertia::render('KDS/Board', [
            'store' => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_code' => $store->store_code,
            ],
            'tickets' => $queryService->activeForStore($store)->values(),
        ]);
    }
}
