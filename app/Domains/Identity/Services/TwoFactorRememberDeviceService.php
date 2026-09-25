<?php

namespace App\Domains\Identity\Services;

use App\Models\TwoFactorRememberedDevice;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TwoFactorRememberDeviceService
{
    public const REMEMBER_DAYS = 7;

    public const COOKIE_NAME = 'ispos_2fa_remember';

    public function remember(User $user): Cookie
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::REMEMBER_DAYS);

        TwoFactorRememberedDevice::query()->create([
            'user_id' => $user->id,
            'token_hash' => $this->hashToken($token),
            'expires_at' => $expiresAt,
        ]);

        $payload = encrypt([
            'user_id' => $user->id,
            'token' => $token,
        ]);

        return cookie(
            self::COOKIE_NAME,
            $payload,
            self::REMEMBER_DAYS * 24 * 60,
            '/',
            null,
            (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    public function hasValidRememberedDevice(Request $request, User $user): bool
    {
        $payload = $request->cookie(self::COOKIE_NAME);

        if (! is_string($payload) || $payload === '') {
            return false;
        }

        try {
            /** @var array{user_id?: string, token?: string} $data */
            $data = decrypt($payload);
        } catch (DecryptException) {
            return false;
        }

        $userId = $data['user_id'] ?? null;
        $token = $data['token'] ?? '';

        if ($userId !== $user->id || $token === '') {
            return false;
        }

        return TwoFactorRememberedDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $this->hashToken($token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function forgetCookie(): Cookie
    {
        return cookie()->forget(self::COOKIE_NAME);
    }

    public function revokeAllForUser(User $user): void
    {
        TwoFactorRememberedDevice::query()
            ->where('user_id', $user->id)
            ->delete();
    }

    public function purgeExpired(): int
    {
        return TwoFactorRememberedDevice::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    protected function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
