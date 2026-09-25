<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Support\PosUiResolver;
use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosUiSettingsController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
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

        if ($storeId && ! $user->canAccessStore($storeId)) {
            abort(403);
        }

        $store = $storeId ? Store::query()->find($storeId) : null;

        return Inertia::render('Admin/PosUi/Edit', [
            'companyId' => $companyId,
            'storeId' => $storeId,
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
                ->get(['id', 'store_name', 'store_code', 'company_id', 'store_category']),
            'layoutOptions' => PosUiResolver::layoutOptions(),
            'categoryDefaults' => PosUiResolver::categoryDefaults(),
            'companyUiLayout' => $companyId
                ? (string) $this->settingService->get('pos.ui_layout', PosUiResolver::LAYOUT_AUTO, $companyId)
                : PosUiResolver::LAYOUT_AUTO,
            'storeUiLayout' => $storeId
                ? (string) $this->settingService->get('pos.ui_layout', PosUiResolver::LAYOUT_AUTO, $companyId, $storeId)
                : PosUiResolver::LAYOUT_AUTO,
            'resolvedStoreLayout' => $store
                ? app(PosUiResolver::class)->resolve($store)
                : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $request->validate([
            'scope' => ['required', Rule::in(['company', 'store'])],
            'scope_id' => ['required', 'ulid'],
            'ui_layout' => ['required', Rule::in(array_merge([PosUiResolver::LAYOUT_AUTO], PosUiResolver::selectableLayouts()))],
        ]);

        $user = $request->user();

        if ($data['scope'] === 'company') {
            abort_unless($user->canAccessCompany($data['scope_id']), 403);
            $scope = Setting::SCOPE_COMPANY;
        } else {
            abort_unless($user->canAccessStore($data['scope_id']), 403);
            $scope = Setting::SCOPE_STORE;
        }

        $this->settingService->set('pos.ui_layout', $data['ui_layout'], $scope, $data['scope_id']);

        $this->auditLogger->log('update', 'pos_ui_settings', Setting::class, $data['scope_id'], null, $data);

        return back()->with('success', 'POS UI settings saved.');
    }
}
