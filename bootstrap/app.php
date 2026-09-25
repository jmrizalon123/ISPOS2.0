<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'pos.device' => \App\Http\Middleware\ResolvePosDevice::class,
            'backoffice.context' => \App\Http\Middleware\EnsureBackofficeContext::class,
            'backoffice.company' => \App\Http\Middleware\EnsureCompanyScope::class,
            'storefront.customer' => \App\Http\Middleware\EnsureStorefrontCustomer::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('store/*')) {
                $slug = $request->route('slug');

                return $slug
                    ? route('storefront.login', $slug)
                    : route('login');
            }

            return route('login');
        });

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->header('X-Inertia')) {
                return Inertia::location(route('login'));
            }

            return redirect()
                ->route('login')
                ->with('status', __('auth.session_expired'));
        });
    })->create();
