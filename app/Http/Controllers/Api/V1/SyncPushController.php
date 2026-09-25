<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sync\Services\SyncPushService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncPushRequest;
use App\Models\PosDevice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SyncPushController extends Controller
{
    public function __invoke(SyncPushRequest $request, SyncPushService $syncPushService): JsonResponse
    {
        /** @var PosDevice $device */
        $device = $request->attributes->get('posDevice');

        $result = $syncPushService->pushSales(
            $device,
            $request->user(),
            $request->validated('sales'),
        );

        return ApiResponse::success($result, 'Sync push processed.');
    }
}
