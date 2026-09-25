<?php

namespace App\Http\Middleware;

use App\Services\UserPreferenceService;
use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        protected UserPreferenceService $userPreferenceService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('backoffice.locales.default', 'en');

        if ($user = $request->user()) {
            $preferences = $this->userPreferenceService->resolve($user);
            $locale = $preferences['locale'] ?? $locale;
        } elseif ($request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }

        app()->setLocale(Locale::normalize($locale));

        return $next($request);
    }
}
