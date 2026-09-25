<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Domains\Inventory\Services\LowStockAlertService;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\IsposBrandingService;
use App\Services\TranslationConfigService;
use App\Services\UserPreferenceService;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'backoffice::app';

    public function rootView(Request $request): string
    {
        return config('backoffice.inertia_root_view', $this->rootView);
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $contextService = app(BackofficeContextService::class);
        $preferences = app(UserPreferenceService::class)->resolve($user);

        $isposBranding = app(IsposBrandingService::class)->forFrontend();
        $brandingCompany = $user ? $this->brandingCompany($user) : null;

        View::share('userPreferences', $preferences);
        View::share('appDisplayName', $isposBranding['name']);
        View::share('appLogoUrl', $isposBranding['logo_url']);
        View::share('appTitle', $brandingCompany
            ? $isposBranding['name'].' | '.$brandingCompany['name']
            : $isposBranding['name']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'email_verified_at' => $user->email_verified_at,
                    'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                    'company_id' => $user->company_id,
                    'preferred_store_id' => $user->default_store_id,
                    'default_store_id' => $user->default_store_id,
                    'base_type' => $user->base_type,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'preferences' => $preferences,
                    'company' => $brandingCompany,
                ] : null,
                'customer' => $this->sharedCustomer($request),
                'context' => $user ? $contextService->sharePayload($user) : null,
                'canSwitchContext' => $user ? $contextService->canSwitchContext($user) : false,
                'contextOptions' => $user && $contextService->isSet()
                    ? $contextService->selectionOptions($user)
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'print_sale_id' => fn () => $request->session()->get('print_sale_id'),
                'print_change' => fn () => $request->session()->get('print_change'),
            ],
            'app' => [
                'name' => $isposBranding['name'],
                'csrf_token' => csrf_token(),
                'pos_url' => config('backoffice.pos_app_url'),
                'branding' => $isposBranding,
            ],
            'locale' => [
                'current' => app()->getLocale(),
                'default' => config('backoffice.locales.default', 'en'),
                'supported' => Locale::catalog(),
            ],
            'translation' => fn () => app(TranslationConfigService::class)->forFrontend(),
            'alerts' => fn () => $this->sharedAlerts($user),
        ];
    }

    /** @return array{low_stock_count: int, low_stock_items: list<array<string, mixed>>} */
    private function sharedAlerts(?User $user): array
    {
        if (! $user?->can('inventory.view')) {
            return ['low_stock_count' => 0, 'low_stock_items' => []];
        }

        $contextService = app(BackofficeContextService::class);
        $companyId = $user->hasGlobalOrganizationAccess() ? null : $user->company_id;
        $storeId = $contextService->effectiveStoreId($user);
        $service = app(LowStockAlertService::class);

        return [
            'low_stock_count' => $service->count($user, $companyId, $storeId),
            'low_stock_items' => $service->list($user, $companyId, $storeId, 5)
                ->map(function ($inventory) {
                    $product = $inventory->product;
                    $qty = (float) $inventory->qty;
                    $warning = $product?->warning_qty !== null ? (float) $product->warning_qty : null;
                    $status = 'ok';
                    if ($qty <= 0) {
                        $status = 'out';
                    } elseif ($warning !== null && $qty <= $warning) {
                        $status = 'low';
                    }

                    return [
                        'id' => $product?->id ?? $inventory->id,
                        'name' => $product?->name ?? 'Product',
                        'sku' => $product?->sku ?? '',
                        'qty' => $inventory->qty,
                        'warning_qty' => $product?->warning_qty,
                        'stock_status' => $status,
                        'store_name' => $inventory->store?->store_name,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: string, name: string, email: string|null}|null
     */
    private function sharedCustomer(Request $request): ?array
    {
        if (! $request->is('store/*')) {
            return null;
        }

        /** @var Customer|null $customer */
        $customer = $request->user('customer');
        if (! $customer) {
            return null;
        }

        return [
            'id' => $customer->id,
            'name' => $customer->displayName(),
            'email' => $customer->email,
        ];
    }

    /**
     * @return array{id: string, name: string, code: string}|null
     */
    private function brandingCompany(User $user): ?array
    {
        $user->loadMissing('company');

        $company = $user->company;

        if ($user->company_id && ! $company) {
            $user->forceFill(['company_id' => null])->save();
        }

        if (! $company && $user->hasGlobalOrganizationAccess() && Company::query()->count() === 1) {
            $company = Company::query()->first();
            $user->forceFill(['company_id' => $company->id])->save();
        }

        if (! $company) {
            return null;
        }

        return [
            'id' => $company->id,
            'name' => $company->display_name ?: $company->name,
            'company_code' => $company->company_code,
            'display_name' => $company->display_name,
        ];
    }
}
