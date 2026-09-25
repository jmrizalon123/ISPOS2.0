<?php

namespace App\Domains\OnlineStore\Services;

use App\Models\OnlineStoreSetting;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineStoreSettingService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function paginate(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        return OnlineStoreSetting::query()
            ->with(['company:id,name,display_name', 'store:id,store_name,store_code,store_category,company_id'])
            ->when(! $user->hasGlobalOrganizationAccess(), function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('slug', 'like', "%{$search}%")
                        ->orWhere('storefront_name', 'like', "%{$search}%")
                        ->orWhereHas('store', fn ($s) => $s->where('store_name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForStore(Store $store, array $data, User $user): OnlineStoreSetting
    {
        $existing = OnlineStoreSetting::query()->where('store_id', $store->id)->first();

        $slug = Str::slug((string) ($data['slug'] ?? $store->store_code ?? $store->store_name));
        if ($slug === '') {
            $slug = 'store-'.Str::lower(Str::random(6));
        }

        $slugConflict = OnlineStoreSetting::query()
            ->where('slug', $slug)
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->exists();

        if ($slugConflict) {
            throw ValidationException::withMessages(['slug' => 'This storefront URL slug is already taken.']);
        }

        $payload = [
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'slug' => $slug,
            'is_published' => (bool) ($data['is_published'] ?? false),
            'storefront_name' => $data['storefront_name'] ?? $store->store_name,
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'primary_color' => $data['primary_color'] ?? '#0f766e',
            'accent_color' => $data['accent_color'] ?? '#f59e0b',
            'accept_pickup' => (bool) ($data['accept_pickup'] ?? $store->enable_pickup),
            'accept_delivery' => (bool) ($data['accept_delivery'] ?? $store->enable_delivery),
            'accept_dine_in' => (bool) ($data['accept_dine_in'] ?? $store->enable_dine_in),
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'delivery_fee' => $data['delivery_fee'] ?? 0,
            'free_delivery_threshold' => $data['free_delivery_threshold'] ?? null,
            'preparation_minutes' => (int) ($data['preparation_minutes'] ?? 30),
            'payment_methods' => $data['payment_methods'] ?? ['cod', 'pay_at_store'],
            'auto_accept_orders' => (bool) ($data['auto_accept_orders'] ?? false),
            'announcement' => $data['announcement'] ?? null,
            'support_phone' => $data['support_phone'] ?? $store->phone ?? $store->mobile,
            'support_email' => $data['support_email'] ?? $store->email,
            'orders_open_at' => $data['orders_open_at'] ?? $store->opening_time,
            'orders_close_at' => $data['orders_close_at'] ?? $store->closing_time,
            'orders_open_days' => $data['orders_open_days'] ?? $store->operating_days,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'homepage' => $data['homepage'] ?? ($existing?->homepage),
            'status' => $data['status'] ?? 'active',
            'updated_by' => $user->id,
        ];

        if (array_key_exists('logo', $data)) {
            $payload['logo'] = $data['logo'] ?: null;
        }
        if (array_key_exists('hero_image', $data)) {
            $payload['hero_image'] = $data['hero_image'] ?: null;
        }

        if (array_key_exists('homepage', $data) && is_array($data['homepage'])) {
            $payload['homepage'] = $data['homepage'];
        }

        if ($existing) {
            $before = $existing->toArray();
            $existing->update($payload);
            $this->auditLogger->log('update', 'online_store_settings', OnlineStoreSetting::class, $existing->id, $before, $existing->fresh()->toArray(), $user);

            return $existing->fresh(['store', 'company']);
        }

        $payload['created_by'] = $user->id;
        $setting = OnlineStoreSetting::create($payload);
        $this->auditLogger->log('create', 'online_store_settings', OnlineStoreSetting::class, $setting->id, null, $setting->toArray(), $user);

        return $setting->load(['store', 'company']);
    }

    public function delete(OnlineStoreSetting $setting, ?User $user = null): void
    {
        $before = $setting->toArray();
        $setting->delete();
        $this->auditLogger->log('delete', 'online_store_settings', OnlineStoreSetting::class, $setting->id, $before, null, $user);
    }

    public function findPublishedBySlug(string $slug): ?OnlineStoreSetting
    {
        return OnlineStoreSetting::query()
            ->with(['store.company', 'store.priceGroup'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where('status', 'active')
            ->whereHas('store', function ($q) {
                $q->where('enable_online_ordering', true)
                    ->where('is_active', true)
                    ->where('status', 'active');
            })
            ->whereHas('store.company', function ($q) {
                $q->where('enable_ecommerce', true)
                    ->where('is_active', true);
            })
            ->first();
    }

    public function ensureDefaults(Store $store, User $user): OnlineStoreSetting
    {
        $existing = OnlineStoreSetting::query()->where('store_id', $store->id)->first();
        if ($existing) {
            return $existing;
        }

        return $this->upsertForStore($store, [
            'slug' => $store->store_code ?: Str::slug($store->store_name),
            'is_published' => false,
            'storefront_name' => $store->store_name,
        ], $user);
    }
}
