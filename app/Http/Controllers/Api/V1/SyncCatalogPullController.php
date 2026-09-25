<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sync\Services\SyncBootstrapService;
use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncCatalogPullController extends Controller
{
    public function __invoke(Request $request, SyncBootstrapService $bootstrapService): JsonResponse
    {
        /** @var PosDevice $device */
        $device = $request->attributes->get('posDevice');

        return ApiResponse::success(
            $bootstrapService->pullCatalogChanges($device, $request->string('since')->toString() ?: null),
        );
    }
}
