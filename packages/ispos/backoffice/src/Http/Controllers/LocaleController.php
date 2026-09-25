<?php

namespace Ispos\Backoffice\Http\Controllers;

use App\Services\UserPreferenceService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __construct(
        protected UserPreferenceService $userPreferenceService,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        abort_unless(Locale::isSupported($data['locale']), 422, 'Unsupported locale.');

        if ($user = $request->user()) {
            $this->userPreferenceService->updateLocale($user, $data['locale']);
        } else {
            $request->session()->put('locale', $data['locale']);
        }

        app()->setLocale($data['locale']);

        return back();
    }
}
