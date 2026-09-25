<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Domains\OnlineStore\Services\StorefrontHeroMediaService;
use App\Domains\OnlineStore\Support\StorefrontHomepageDefaults;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreOnlineStoreSettingRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateOnlineStoreSettingRequest;
use App\Models\OnlineStoreSetting;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnlineStoreSettingController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(
        protected OnlineStoreSettingService $settings,
        protected StorefrontHeroMediaService $heroMedia,
    ) {
        $this->authorizeResource(OnlineStoreSetting::class, 'online_store_setting');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/OnlineStore/Settings/Index', [
            'settings' => $this->settings->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/OnlineStore/Settings/Form', [
            'setting' => null,
            'homepageDefaults' => StorefrontHomepageDefaults::all(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->availableStores($request),
        ]);
    }

    public function store(StoreOnlineStoreSettingRequest $request): RedirectResponse
    {
        $store = Store::query()->findOrFail($request->validated('store_id'));
        $this->authorize('view', $store);

        $data = $request->validated();
        $data['homepage'] = $this->processHeroSlides(
            $request,
            is_array($data['homepage'] ?? null) ? $data['homepage'] : [],
            null,
        );

        $setting = $this->settings->upsertForStore($store, $data, $request->user());

        return redirect()
            ->route('admin.online-store-settings.edit', $setting)
            ->with('success', 'Online store settings saved.');
    }

    public function edit(Request $request, OnlineStoreSetting $onlineStoreSetting): Response
    {
        $onlineStoreSetting->load(['store:id,store_name,store_code,store_category,company_id', 'company:id,name,display_name']);

        $setting = $onlineStoreSetting->toArray();
        $setting['logo_url'] = $onlineStoreSetting->logo_url;
        $setting['hero_image_url'] = $onlineStoreSetting->hero_image_url;
        $setting['public_url'] = $onlineStoreSetting->public_url;
        $setting['homepage'] = StorefrontHomepageDefaults::merge($onlineStoreSetting->homepage);

        return Inertia::render('Admin/OnlineStore/Settings/Form', [
            'setting' => $setting,
            'homepageDefaults' => StorefrontHomepageDefaults::all(),
            'companies' => $this->companyOptions($request),
            'stores' => $this->availableStores($request, $onlineStoreSetting->store_id),
        ]);
    }

    public function update(UpdateOnlineStoreSettingRequest $request, OnlineStoreSetting $onlineStoreSetting): RedirectResponse
    {
        $store = Store::query()->findOrFail($request->validated('store_id'));
        $this->authorize('view', $store);

        $data = $request->validated();
        $data['homepage'] = $this->processHeroSlides(
            $request,
            is_array($data['homepage'] ?? null) ? $data['homepage'] : [],
            $onlineStoreSetting->homepage,
        );

        $this->settings->upsertForStore($store, $data, $request->user());

        return redirect()
            ->route('admin.online-store-settings.index')
            ->with('success', 'Online store settings updated.');
    }

    public function publish(Request $request, OnlineStoreSetting $onlineStoreSetting): RedirectResponse
    {
        $this->authorize('publish', $onlineStoreSetting);

        $onlineStoreSetting->update([
            'is_published' => ! $onlineStoreSetting->is_published,
            'updated_by' => $request->user()->id,
        ]);

        $label = $onlineStoreSetting->is_published ? 'published' : 'unpublished';

        return back()->with('success', "Storefront {$label}.");
    }

    public function destroy(Request $request, OnlineStoreSetting $onlineStoreSetting): RedirectResponse
    {
        $this->settings->delete($onlineStoreSetting, $request->user());

        return redirect()
            ->route('admin.online-store-settings.index')
            ->with('success', 'Storefront deleted.');
    }

    /**
     * @param  array<string, mixed>  $homepage
     * @param  array<string, mixed>|null  $previousHomepage
     * @return array<string, mixed>
     */
    protected function processHeroSlides(Request $request, array $homepage, ?array $previousHomepage): array
    {
        $incoming = data_get($homepage, 'hero.slides');
        if (! is_array($incoming)) {
            return $homepage;
        }

        $previousSlides = data_get($previousHomepage, 'hero.slides', []);
        $previousByIndex = is_array($previousSlides) ? array_values($previousSlides) : [];
        $keptPaths = [];
        $normalized = [];

        foreach (array_values($incoming) as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $previousPath = is_array($previousByIndex[$index] ?? null)
                ? (string) ($previousByIndex[$index]['image'] ?? '')
                : '';

            $remove = filter_var($slide['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $upload = $request->file("homepage.hero.slides.$index.image");

            if ($upload instanceof UploadedFile) {
                $this->assertValidHeroImage($upload, $index);
                $path = $this->heroMedia->replace($previousPath ?: null, $upload);
            } elseif ($remove) {
                $this->heroMedia->forget($previousPath ?: null);
                $path = '';
            } else {
                $existing = $slide['image'] ?? $previousPath;
                $path = is_string($existing) ? $existing : '';
            }

            if ($path !== '') {
                $keptPaths[] = $path;
            }

            unset($slide['remove_image'], $slide['image_url'], $slide['preview_url']);

            $normalized[] = array_merge(StorefrontHomepageDefaults::emptySlide(), [
                'image' => $path,
                'eyebrow' => (string) ($slide['eyebrow'] ?? ''),
                'headline' => (string) ($slide['headline'] ?? ''),
                'subheadline' => (string) ($slide['subheadline'] ?? ''),
                'primary_cta_label' => (string) ($slide['primary_cta_label'] ?? ''),
                'primary_cta_target' => (string) ($slide['primary_cta_target'] ?? 'shop'),
                'secondary_cta_label' => (string) ($slide['secondary_cta_label'] ?? ''),
                'secondary_cta_target' => (string) ($slide['secondary_cta_target'] ?? 'categories'),
            ]);
        }

        foreach ($previousByIndex as $previousSlide) {
            if (! is_array($previousSlide)) {
                continue;
            }
            $oldPath = (string) ($previousSlide['image'] ?? '');
            if ($oldPath !== '' && ! in_array($oldPath, $keptPaths, true)) {
                $this->heroMedia->forget($oldPath);
            }
        }

        data_set($homepage, 'hero.slides', $normalized);

        return $homepage;
    }

    protected function assertValidHeroImage(UploadedFile $file, int $index): void
    {
        $validator = validator(
            ['image' => $file],
            ['image' => 'required|image|max:'.StorefrontHeroMediaService::MAX_KILOBYTES],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "homepage.hero.slides.$index.image" => $validator->errors()->first('image'),
            ]);
        }
    }

    /**
     * @return list<array{id: string, store_name: string, store_code: string, company_id: string, store_category: string|null}>
     */
    protected function availableStores(Request $request, ?string $includeStoreId = null): array
    {
        $configuredIds = OnlineStoreSetting::query()->pluck('store_id')->all();

        return Store::query()
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when(
                $includeStoreId,
                fn ($q) => $q->where(function ($inner) use ($configuredIds, $includeStoreId) {
                    $inner->whereNotIn('id', $configuredIds)->orWhere('id', $includeStoreId);
                }),
                fn ($q) => $q->whereNotIn('id', $configuredIds),
            )
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id', 'store_category'])
            ->all();
    }
}
