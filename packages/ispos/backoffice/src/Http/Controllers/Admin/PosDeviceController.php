<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Sync\Services\PosDeviceQueryService;
use App\Domains\Sync\Services\PosDeviceService;
use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosDeviceController extends Controller
{
    public function __construct(
        protected PosDeviceQueryService $queryService,
        protected PosDeviceService $posDeviceService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PosDevice::class);

        $user = $request->user();

        $stores = Store::query()
            ->when(! $user->hasGlobalOrganizationAccess(), function ($query) use ($user) {
                if ($user->hasRole('Company Admin')) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereIn('id', $user->stores()->pluck('stores.id'));
                }
            })
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code']);

        return Inertia::render('Admin/PosDevices/Index', [
            'devices' => $this->queryService->paginate(
                $user,
                $request->string('search')->toString(),
                $request->string('store_id')->toString(),
                $request->string('status')->toString(),
            ),
            'stores' => $stores,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'store_id' => $request->string('store_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function revoke(PosDevice $posDevice): RedirectResponse
    {
        $this->authorize('revoke', $posDevice);

        $this->posDeviceService->revoke($posDevice);

        return back()->with('success', 'POS device revoked.');
    }
}
