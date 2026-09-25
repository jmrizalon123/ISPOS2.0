<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Identity\Services\StoreEmployeeService;
use App\Models\Store;
use App\Models\User;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\BulkStoreEmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class StoreEmployeeController extends Controller
{
    public function __construct(protected StoreEmployeeService $storeEmployeeService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $contextService = app(BackofficeContextService::class);
        $stores = $this->storeEmployeeService->storeSummaries($request->user());
        $contextStoreId = $contextService->effectiveStoreId($request->user());
        $requestedStoreId = $request->string('store_id')->toString() ?: null;
        $allowedStoreIds = collect($stores)->pluck('id');

        if ($contextStoreId) {
            $storeId = $contextStoreId;
        } else {
            $storeId = $requestedStoreId && $allowedStoreIds->contains($requestedStoreId)
                ? $requestedStoreId
                : ($stores[0]['id'] ?? null);
        }

        if ($requestedStoreId && $storeId !== $requestedStoreId) {
            abort(403);
        }

        $search = $request->string('search')->toString() ?: null;
        $store = $storeId ? Store::query()->find($storeId) : null;

        return Inertia::render('Admin/StoreEmployees/Index', [
            'stores' => $stores,
            'lockStoreSelection' => $contextStoreId !== null,
            'selectedStoreId' => $storeId,
            'assignments' => $storeId
                ? $this->storeEmployeeService->listForStore($request->user(), $storeId, $search)
                : [],
            'availableUsers' => $store ? $this->storeEmployeeService->availableUsers($request->user(), $store) : [],
            'filters' => [
                'search' => $search ?? '',
                'store_id' => $storeId ?? '',
            ],
        ]);
    }

    public function bulkStore(BulkStoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $assigned = $this->storeEmployeeService->assignMany(
            $request->user(),
            $data['store_id'],
            $data['user_ids'],
        );

        $message = $assigned === 1
            ? '1 user assigned to the store.'
            : "{$assigned} users assigned to the store.";

        return redirect()
            ->route('admin.users.store-employees.index', ['store_id' => $data['store_id']])
            ->with('success', $message);
    }

    public function destroy(User $user, Store $store): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->storeEmployeeService->remove(request()->user(), $user, $store);

        return redirect()
            ->route('admin.users.store-employees.index', ['store_id' => $store->id])
            ->with('success', 'Store assignment removed.');
    }

}
