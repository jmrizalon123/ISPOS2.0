<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Register;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosProvisionBindController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'register_code' => ['required', 'string', 'max:50'],
            'store_code' => ['nullable', 'string', 'max:50'],
            'device_serial' => ['required', 'string', 'max:100'],
        ]);

        $register = $this->resolveRegister(
            strtoupper(trim($validated['register_code'])),
            isset($validated['store_code']) ? strtoupper(trim($validated['store_code'])) : null,
        );

        $deviceSerial = strtoupper(trim($validated['device_serial']));
        $existing = $register->device_serial ? strtoupper(trim((string) $register->device_serial)) : null;

        if ($existing && ! $register->reset_registration && $existing !== $deviceSerial) {
            throw ValidationException::withMessages([
                'register_code' => 'This register code is already activated on another POS terminal.',
            ]);
        }

        $register->forceFill([
            'device_serial' => $deviceSerial,
            'reset_registration' => false,
        ])->save();

        return ApiResponse::success([
            'register' => $register->fresh()->toArray(),
        ], 'Register bound to POS terminal.');
    }

    protected function resolveRegister(string $registerCode, ?string $storeCode): Register
    {
        $query = Register::query()
            ->with('store:id,store_code')
            ->where('register_code', $registerCode)
            ->where('status', 'active')
            ->where('is_active', true);

        if ($storeCode) {
            $query->whereHas('store', fn ($store) => $store->where('store_code', $storeCode));
        }

        $registers = $query->get();

        if ($registers->isEmpty()) {
            throw ValidationException::withMessages(['register_code' => 'Register code not found or inactive.']);
        }

        if ($registers->count() > 1) {
            throw ValidationException::withMessages([
                'register_code' => 'Multiple registers match this code. Provide store_code to identify the branch.',
            ]);
        }

        return $registers->first();
    }
}
