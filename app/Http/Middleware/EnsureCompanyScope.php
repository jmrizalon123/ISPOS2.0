<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Services\BackofficeContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyScope
{
    public function __construct(protected BackofficeContextService $contextService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->contextService->isCompanyScope()) {
            return $next($request);
        }

        abort(403, 'This module is only available in company scope.');
    }
}
