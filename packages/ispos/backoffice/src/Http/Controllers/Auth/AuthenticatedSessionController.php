<?php

namespace Ispos\Backoffice\Http\Controllers\Auth;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Identity\Services\TwoFactorRememberDeviceService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        BackofficeContextService $contextService,
        TwoFactorRememberDeviceService $rememberDeviceService,
    ): RedirectResponse {
        $request->authenticate();

        /** @var User $user */
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            if ($rememberDeviceService->hasValidRememberedDevice($request, $user)) {
                $request->session()->regenerate();

                $contextService->clear();

                if ($contextService->attemptAutoResolve($user)) {
                    return redirect()->intended(route('dashboard', absolute: false));
                }

                return redirect()->intended(route('login-context.create', absolute: false));
            }

            Auth::logout();

            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

        $contextService->clear();

        if ($contextService->attemptAutoResolve($request->user())) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->intended(route('login-context.create', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AuditLogger $auditLogger, BackofficeContextService $contextService): RedirectResponse
    {
        if ($user = $request->user()) {
            $auditLogger->log('logout', 'auth', User::class, $user->id, null, ['email' => $user->email], $user, $request);
        }

        $contextService->clear();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
