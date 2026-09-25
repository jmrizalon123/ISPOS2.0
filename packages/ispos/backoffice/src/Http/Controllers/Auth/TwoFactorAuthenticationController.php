<?php

namespace Ispos\Backoffice\Http\Controllers\Auth;

use App\Domains\Identity\Services\TwoFactorAuthenticationService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    public function enable(Request $request, TwoFactorAuthenticationService $twoFactorService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return back();
        }

        $twoFactorService->beginSetup($user);

        return back()->with('success', __('profile.twoFactorSetupStarted'));
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactorService): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user()->fresh();

        if (! $user->hasPendingTwoFactorAuthentication()) {
            return back();
        }

        $recoveryCodes = $twoFactorService->confirm($user, $validated['code']);

        if ($recoveryCodes === []) {
            throw ValidationException::withMessages([
                'code' => __('profile.twoFactorInvalidCode'),
            ]);
        }

        return back()
            ->with('success', __('profile.twoFactorEnabledSuccess'))
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request, TwoFactorAuthenticationService $twoFactorService): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $twoFactorService->disable($request->user());

        return back()->with('success', __('profile.twoFactorDisabledSuccess'));
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactorService): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return back();
        }

        $recoveryCodes = $twoFactorService->regenerateRecoveryCodes($user);

        return back()
            ->with('success', __('profile.twoFactorRecoveryRegenerated'))
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }
}
