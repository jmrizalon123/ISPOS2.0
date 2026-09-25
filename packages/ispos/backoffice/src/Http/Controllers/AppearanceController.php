<?php

namespace Ispos\Backoffice\Http\Controllers;

use Ispos\Backoffice\Http\Requests\UpdateAppearanceRequest;
use App\Services\UserPreferenceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function __construct(
        protected UserPreferenceService $userPreferenceService,
    ) {}

    public function edit(): Response
    {
        $user = request()->user();

        return Inertia::render('Appearance/Edit', [
            'preferences' => $this->userPreferenceService->resolve($user),
        ]);
    }

    public function update(UpdateAppearanceRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->userPreferenceService->update($user, $request->validated());

        return back()->with('success', 'Appearance saved.');
    }
}
