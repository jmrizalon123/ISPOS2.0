<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\UpdateIsposBrandingSettingsRequest;
use Ispos\Backoffice\Http\Requests\UpdateTranslationSettingsRequest;
use App\Models\Company;
use App\Models\Register;
use App\Models\Setting;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\IsposBrandingService;
use App\Services\SettingService;
use App\Services\TranslationConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
        protected TranslationConfigService $translationConfigService,
        protected IsposBrandingService $isposBrandingService,
        protected AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): Response
    {
        $this->authorize('settings.view');

        $user = $request->user();
        $companyId = $user->hasGlobalOrganizationAccess()
            ? ($request->string('company_id')->toString() ?: $user->company_id)
            : $user->company_id;
        $storeId = $request->string('store_id')->toString() ?: null;
        $registerId = $request->string('register_id')->toString() ?: null;

        if ($storeId && ! $user->canAccessStore($storeId)) {
            abort(403);
        }

        if ($registerId) {
            $register = Register::query()->with('store')->findOrFail($registerId);
            abort_unless($user->canAccessStore($register->store_id), 403);
            $storeId = $register->store_id;
            $companyId = $register->store->company_id;
        }

        return Inertia::render('Admin/Settings/Edit', [
            'companyId' => $companyId,
            'storeId' => $storeId,
            'registerId' => $registerId,
            'companies' => $user->hasGlobalOrganizationAccess()
                ? Company::query()->orderBy('name')->get(['id', 'name', 'company_code', 'display_name'])
                : Company::query()->whereKey($user->company_id)->get(['id', 'name', 'company_code', 'display_name']),
            'stores' => Store::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when(! $user->hasGlobalOrganizationAccess(), function ($q) use ($user) {
                    if ($user->hasRole('Company Admin')) {
                        $q->where('company_id', $user->company_id);
                    } else {
                        $q->whereIn('id', $user->stores()->pluck('stores.id'));
                    }
                })
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code', 'company_id']),
            'registers' => Register::query()
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->when(! $storeId && $companyId, fn ($q) => $q->whereHas('store', fn ($s) => $s->where('company_id', $companyId)))
                ->orderBy('register_name')
                ->get(['id', 'register_name', 'register_code', 'store_id']),
            'companySettings' => $companyId ? $this->flatten($this->settingService->getForScopes(Setting::SCOPE_COMPANY, $companyId)) : [],
            'storeSettings' => $storeId ? $this->flatten($this->settingService->getForScopes(Setting::SCOPE_STORE, $storeId)) : [],
            'registerSettings' => $registerId ? $this->flatten($this->settingService->getForScopes(Setting::SCOPE_REGISTER, $registerId)) : [],
            'resolved' => [
                'receipt_footer' => $this->settingService->get('company.receipt_footer', '', $companyId, $storeId, $registerId),
                'tax_rate' => $this->settingService->get('company.tax_rate', 12, $companyId, $storeId, $registerId),
            ],
            'translationSettings' => $this->translationConfigService->forAdmin(),
            'isposBrandingSettings' => $this->isposBrandingService->forAdmin(),
        ]);
    }

    public function updateIsposBranding(UpdateIsposBrandingSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->isposBrandingService->update(
            $validated,
            $request->file('logo'),
        );

        $this->auditLogger->log('update', 'ispos_branding_settings', Setting::class, null, null, $request->safe()->except('logo'));

        return back()->with('success', 'iSPOS settings saved.');
    }

    public function updateTranslation(UpdateTranslationSettingsRequest $request): RedirectResponse
    {
        $this->translationConfigService->update($request->validated());

        $this->auditLogger->log('update', 'translation_settings', Setting::class, null, null, $request->safe()->except('google_api_key'));

        return back()->with('success', 'Translation settings saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $request->validate([
            'scope' => ['required', 'in:company,store,register'],
            'scope_id' => ['required', 'ulid'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        if ($data['scope'] === 'company') {
            abort_unless($user->canAccessCompany($data['scope_id']), 403);
        }

        if ($data['scope'] === 'store') {
            abort_unless($user->canAccessStore($data['scope_id']), 403);
        }

        if ($data['scope'] === 'register') {
            $register = Register::query()->findOrFail($data['scope_id']);
            abort_unless($user->canAccessStore($register->store_id), 403);
        }

        $this->settingService->set('company.receipt_footer', $data['receipt_footer'] ?? '', $data['scope'], $data['scope_id']);
        $this->settingService->set('company.tax_rate', (float) ($data['tax_rate'] ?? 12), $data['scope'], $data['scope_id']);

        $this->auditLogger->log('update', 'settings', Setting::class, $data['scope_id'], null, $data);

        return back()->with('success', 'Settings saved.');
    }

    protected function flatten(array $settings): array
    {
        $out = [];

        foreach ($settings as $key => $value) {
            $out[$key] = is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value;
        }

        return $out;
    }
}
