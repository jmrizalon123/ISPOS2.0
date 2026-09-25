<?php

namespace Ispos\Backoffice\Http\Controllers\Auth;

use App\Domains\Identity\Services\BackofficeContextService;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginContextController extends Controller
{
    public function create(Request $request, BackofficeContextService $contextService): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($contextService->isSet() && $contextService->validateCurrentContext($user)) {
            return redirect()->route('dashboard');
        }

        $options = $contextService->selectionOptions($user);

        if ($contextService->selectionChoiceCount($user) <= 1) {
            if ($contextService->attemptAutoResolve($user)) {
                return redirect()->intended(route('dashboard', absolute: false));
            }
        }

        $defaultStoreId = $user->default_store_id;
        $storeIds = collect($options['stores'])->pluck('id');

        return Inertia::render('Auth/SelectContext', [
            'options' => $options,
            'defaultScope' => $options['can_select_company'] && ! $defaultStoreId
                ? 'company'
                : 'store',
            'defaultStoreId' => $defaultStoreId && $storeIds->contains($defaultStoreId)
                ? $defaultStoreId
                : ($options['stores'][0]['id'] ?? null),
        ]);
    }

    public function store(Request $request, BackofficeContextService $contextService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'scope' => ['required', 'in:company,store'],
            'store_id' => ['required_if:scope,store', 'nullable', 'string', 'exists:stores,id'],
        ]);

        if ($validated['scope'] === 'company') {
            $contextService->setCompanyScope($user);
        } else {
            $contextService->setStoreScope($user, $validated['store_id']);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
