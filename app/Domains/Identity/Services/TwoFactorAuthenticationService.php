<?php

namespace App\Domains\Identity\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(
        protected Google2FA $google2fa,
        protected TwoFactorRememberDeviceService $rememberDeviceService,
    ) {}

    public function beginSetup(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->google2fa->generateSecretKey(),
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /** @return list<string> */
    public function confirm(User $user, string $code): array
    {
        if (! $this->verifyCode($user, $code)) {
            return [];
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return $recoveryCodes;
    }

    public function disable(User $user): void
    {
        $this->rememberDeviceService->revokeAllForUser($user);

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return $recoveryCodes;
    }

    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if (! is_string($secret) || trim($secret) === '') {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $this->normalizeCode($code));
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $normalized = Str::upper(trim($code));
        $codes = collect($user->two_factor_recovery_codes ?? []);

        if ($codes->isEmpty() || ! $codes->contains($normalized)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $codes
                ->reject(fn (string $recoveryCode) => hash_equals($recoveryCode, $normalized))
                ->values()
                ->all(),
        ])->save();

        return true;
    }

    public function otpAuthUrl(User $user): string
    {
        $issuer = config('app.name', 'iSPOS');

        return $this->google2fa->getQRCodeUrl(
            $issuer,
            $user->email,
            (string) $user->two_factor_secret,
        );
    }

    public function qrCodeSvg(User $user): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(192, 0),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($this->otpAuthUrl($user));
    }

    public function manualKey(User $user): ?string
    {
        $secret = $user->two_factor_secret;

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    /** @return list<string> */
    protected function generateRecoveryCodes(): array
    {
        return Collection::times(self::RECOVERY_CODE_COUNT, fn () => Str::upper(Str::random(10).'-'.Str::random(10)))
            ->all();
    }

    protected function normalizeCode(string $code): string
    {
        return preg_replace('/\s+/', '', $code) ?? $code;
    }
}
