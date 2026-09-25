<?php

namespace App\Domains\Sync\Services;

use App\Models\PosDevice;
use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PosDeviceService
{
    public function register(User $user, Register $register, string $name, ?string $fingerprint = null): array
    {
        $register->loadMissing('store');

        if (! $user->canAccessStore($register->store) && ! $user->hasGlobalOrganizationAccess()) {
            throw ValidationException::withMessages(['register_id' => 'You cannot register a device for this register.']);
        }

        return DB::transaction(function () use ($user, $register, $name, $fingerprint) {
            if ($fingerprint) {
                $existing = PosDevice::query()
                    ->where('company_id', $register->store->company_id)
                    ->where('fingerprint', $fingerprint)
                    ->where('status', 'active')
                    ->first();

                if ($existing) {
                    $this->revokeToken($existing);
                    $existing->update(['status' => 'revoked']);
                }
            }

            $device = PosDevice::create([
                'company_id' => $register->store->company_id,
                'store_id' => $register->store_id,
                'register_id' => $register->id,
                'registered_by' => $user->id,
                'name' => $name,
                'fingerprint' => $fingerprint,
                'status' => 'active',
            ]);

            $token = $user->createToken(
                $this->tokenName($device),
                ['sync:bootstrap', 'sync:push', 'sync:pull'],
            );

            $device->update(['personal_access_token_id' => $token->accessToken->id]);

            return [
                'device' => $device->fresh(['store', 'register']),
                'plain_text_token' => $token->plainTextToken,
            ];
        });
    }

    public function revoke(PosDevice $device): PosDevice
    {
        $this->revokeToken($device);

        $device->update(['status' => 'revoked']);

        return $device->fresh();
    }

    public function resolveFromAccessToken(mixed $token): ?PosDevice
    {
        if (! $token instanceof PersonalAccessToken || ! str_starts_with($token->name, 'pos-device:')) {
            return null;
        }

        $deviceId = substr($token->name, strlen('pos-device:'));

        return PosDevice::query()
            ->with(['store', 'register'])
            ->where('id', $deviceId)
            ->where('status', 'active')
            ->first();
    }

    public function tokenName(PosDevice $device): string
    {
        return 'pos-device:'.$device->id;
    }

    protected function revokeToken(PosDevice $device): void
    {
        if (! $device->personal_access_token_id) {
            return;
        }

        PersonalAccessToken::query()->whereKey($device->personal_access_token_id)->delete();
        $device->update(['personal_access_token_id' => null]);
    }
}
