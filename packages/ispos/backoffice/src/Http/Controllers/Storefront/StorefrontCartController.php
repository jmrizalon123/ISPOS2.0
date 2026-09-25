<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Cart is client-side (localStorage). This controller keeps a route for
 * deep-links and optional future session-cart support.
 */
class StorefrontCartController extends Controller
{
    public function show(string $slug): RedirectResponse
    {
        return redirect()->route('storefront.show', $slug);
    }
}
