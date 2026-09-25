<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStorefrontCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('customer')->check()) {
            $slug = $request->route('slug');

            return redirect()
                ->guest(route('storefront.login', $slug))
                ->with('error', 'Please sign in to continue to checkout.');
        }

        return $next($request);
    }
}
