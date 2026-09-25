<?php

namespace Ispos\Backoffice\Http\Controllers\Auth;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Identity\Services\TwoFactorAuthenticationService;
use App\Domains\Identity\Services\TwoFactorRememberDeviceService;
use App\Models\User;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(
        Request $request,
        TwoFactorAuthenticationService $twoFactorService,
        BackofficeContextService $contextService,
        TwoFactorRememberDeviceService $rememberDeviceService,
    ): RedirectResponse {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'remember_device' => ['sometimes', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        /** @var User $user */
        $user = User::query()->findOrFail($request->session()->get('login.id'));

        $code = $validated['code'];
        $accepted = $twoFactorService->verifyCode($user, $code)
            || $twoFactorService->consumeRecoveryCode($user, $code);

        if (! $accepted) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'code' => __('auth.twoFactorInvalid'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        Auth::login($user, (bool) $request->session()->get('login.remember', false));

        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        $contextService->clear();

        $redirect = $contextService->attemptAutoResolve($user)
            ? redirect()->intended(route('dashboard', absolute: false))
            : redirect()->intended(route('login-context.create', absolute: false));

        if ($request->boolean('remember_device')) {
            return $redirect->withCookie($rememberDeviceService->remember($user));
        }

        return $redirect;
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        $loginId = (string) $request->session()->get('login.id', 'unknown');

        return Str::transliterate('two-factor|'.$loginId.'|'.$request->ip());
    }
}
