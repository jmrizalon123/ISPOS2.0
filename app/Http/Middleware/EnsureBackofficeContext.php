<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Services\BackofficeContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackofficeContext
{
    public function __construct(protected BackofficeContextService $contextService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->contextService->isSet() && $this->contextService->validateCurrentContext($user)) {
            return $next($request);
        }

        if ($this->contextService->wasEstablished()) {
            return $this->redirectExpiredSessionToLogin($request);
        }

        if ($this->contextService->isSet()) {
            $this->contextService->clear();
        }

        if ($this->contextService->attemptAutoResolve($user)) {
            return $next($request);
        }

        return redirect()->route('login-context.create');
    }

    protected function redirectExpiredSessionToLogin(Request $request): Response
    {
        $this->contextService->clear();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', __('auth.session_expired'));
    }
}
