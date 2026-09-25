<?php

namespace App\Http\Middleware;

use App\Domains\Sync\Services\PosDeviceService;
use App\Models\PosDevice;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ResolvePosDevice
{
    public function __construct(protected PosDeviceService $posDeviceService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $device = $this->resolveDevice($request);

        if (! $device) {
            return ApiResponse::error('A registered POS device token is required.', 403);
        }

        $request->attributes->set('posDevice', $device);

        return $next($request);
    }

    protected function resolveDevice(Request $request): ?PosDevice
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $device = $this->posDeviceService->resolveFromAccessToken($token);

            if ($device) {
                return $device;
            }
        }

        $bearer = $request->bearerToken();

        if (! $bearer) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($bearer);

        return $this->posDeviceService->resolveFromAccessToken($accessToken);
    }
}
