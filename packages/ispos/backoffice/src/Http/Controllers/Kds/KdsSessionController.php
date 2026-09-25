<?php

namespace Ispos\Backoffice\Http\Controllers\Kds;

use App\Domains\Kds\Services\KdsContextService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KdsSessionController extends Controller
{
    public function store(Request $request, KdsContextService $contextService): RedirectResponse
    {
        abort_unless($request->user()?->can('kds.view'), 403);

        $validated = $request->validate([
            'store_id' => ['required', 'string', 'exists:stores,id'],
        ]);

        $contextService->setStore($request->user(), $validated['store_id']);

        return redirect()->route('kds.index');
    }
}
