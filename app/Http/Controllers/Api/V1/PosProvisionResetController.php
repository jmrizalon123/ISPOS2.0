<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Register;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosProvisionResetController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'register_code' => ['required', 'string', 'max:50'],
            'store_code' => ['nullable', 'string', 'max:50'],
        ]);

        $register = $this->resolveRegister(
            strtoupper(trim($validated['register_code'])),
            isset($validated['store_code']) ? strtoupper(trim($validated['store_code'])) : null,
        );

        $register->forceFill(['reset_registration' => true])->save();

        return ApiResponse::success([
            'register' => $register->fresh()->toArray(),
        ], 'Register reset flag enabled.');
    }

    protected function resolveRegister(string $registerCode, ?string $storeCode): Register
    {
        $query = Register::query()
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
