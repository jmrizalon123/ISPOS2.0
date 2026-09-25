<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sync\Services\PosDeviceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterPosDeviceRequest;
use App\Models\Register;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PosDeviceRegisterController extends Controller
{
    public function __invoke(RegisterPosDeviceRequest $request, PosDeviceService $posDeviceService): JsonResponse
    {
        $register = Register::query()->with('store')->findOrFail($request->validated('register_id'));

        $result = $posDeviceService->register(
            $request->user(),
            $register,
            $request->validated('name'),
            $request->validated('fingerprint'),
        );

        $device = $result['device'];

        return ApiResponse::success([
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'store_id' => $device->store_id,
                'register_id' => $device->register_id,
                'status' => $device->status,
            ],
            'token' => $result['plain_text_token'],
        ], 'POS device registered.', 201);
    }
}
