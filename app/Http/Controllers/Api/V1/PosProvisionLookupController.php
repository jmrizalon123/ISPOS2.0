<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Register;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosProvisionLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'register_code' => ['required', 'string', 'max:50'],
            'store_code' => ['nullable', 'string', 'max:50'],
        ]);

        $registerCode = strtoupper(trim($validated['register_code']));
        $storeCode = isset($validated['store_code']) ? strtoupper(trim($validated['store_code'])) : null;

        $query = Register::query()
            ->with([
                'store.company',
                'company',
            ])
            ->where('register_code', $registerCode)
            ->where('status', 'active')
            ->where('is_active', true);

        if ($storeCode) {
            $query->whereHas('store', fn ($store) => $store->where('store_code', $storeCode));
        }

        $registers = $query->get();

        if ($registers->isEmpty()) {
            return ApiResponse::error('Register code not found or inactive.', 404);
        }

        if ($registers->count() > 1) {
            return ApiResponse::error(
                'Multiple registers match this code. Provide store_code to identify the branch.',
                422,
            );
        }

        $register = $registers->first();
        $store = $register->store;
        $company = $register->company ?? $store?->company;

        if (! $store || $store->status !== 'active' || ! $company || $company->status !== 'active') {
            return ApiResponse::error('Register store or company is not active.', 422);
        }

        return ApiResponse::success([
            'company' => $this->serializeModel($company),
            'store' => $this->serializeModel($store),
            'register' => $this->serializeModel($register),
        ]);
    }

    /** @return array<string, mixed> */
    protected function serializeModel(object $model): array
    {
        return $model->toArray();
    }
}
