<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesOnlineStoreSettingFields
{
    /**
     * @return array<string, mixed>
     */
    protected function onlineStoreSettingFieldRules(?string $settingId = null): array
    {
        return [
            'store_id' => ['required', 'ulid', 'exists:stores,id'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('online_store_settings', 'slug')->ignore($settingId),
            ],
            'is_published' => ['sometimes', 'boolean'],
            'storefront_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'string', 'max:255'],
            'hero_image' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'accept_pickup' => ['sometimes', 'boolean'],
            'accept_delivery' => ['sometimes', 'boolean'],
            'accept_dine_in' => ['sometimes', 'boolean'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'preparation_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', Rule::in(['cod', 'pay_at_store', 'gcash', 'card', 'bank_transfer'])],
            'auto_accept_orders' => ['sometimes', 'boolean'],
            'announcement' => ['nullable', 'string', 'max:2000'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'orders_open_at' => ['nullable', 'date_format:H:i'],
            'orders_close_at' => ['nullable', 'date_format:H:i'],
            'orders_open_days' => ['nullable', 'array'],
            'orders_open_days.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'homepage' => ['nullable', 'array'],
            'homepage.show_announcement' => ['sometimes', 'boolean'],
            'homepage.announcement_items' => ['nullable', 'array', 'max:8'],
            'homepage.announcement_items.*' => ['nullable', 'string', 'max:255'],
            'homepage.search_placeholder' => ['nullable', 'string', 'max:120'],
            'homepage.nav_links' => ['nullable', 'array', 'max:12'],
            'homepage.nav_links.*.label' => ['nullable', 'string', 'max:80'],
            'homepage.nav_links.*.target' => ['nullable', 'string', 'max:80'],
            'homepage.hero' => ['nullable', 'array'],
            'homepage.hero.eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.hero.headline' => ['nullable', 'string', 'max:255'],
            'homepage.hero.subheadline' => ['nullable', 'string', 'max:1000'],
            'homepage.hero.primary_cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.hero.primary_cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.hero.secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.hero.secondary_cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.hero.social_proof' => ['nullable', 'string', 'max:255'],
            'homepage.hero.show_social_proof' => ['sometimes', 'boolean'],
            'homepage.hero.show_float_cards' => ['sometimes', 'boolean'],
            'homepage.hero.autoplay' => ['sometimes', 'boolean'],
            'homepage.hero.interval_ms' => ['nullable', 'integer', 'min:2000', 'max:30000'],
            'homepage.hero.slides' => ['nullable', 'array', 'max:8'],
            'homepage.hero.slides.*.image' => ['nullable'],
            'homepage.hero.slides.*.remove_image' => ['sometimes', 'boolean'],
            'homepage.hero.slides.*.eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.hero.slides.*.headline' => ['nullable', 'string', 'max:255'],
            'homepage.hero.slides.*.subheadline' => ['nullable', 'string', 'max:1000'],
            'homepage.hero.slides.*.primary_cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.hero.slides.*.primary_cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.hero.slides.*.secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.hero.slides.*.secondary_cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.trust' => ['nullable', 'array'],
            'homepage.trust.show' => ['sometimes', 'boolean'],
            'homepage.trust.items' => ['nullable', 'array', 'max:6'],
            'homepage.trust.items.*.title' => ['nullable', 'string', 'max:120'],
            'homepage.trust.items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'homepage.categories_section' => ['nullable', 'array'],
            'homepage.categories_section.show' => ['sometimes', 'boolean'],
            'homepage.categories_section.title' => ['nullable', 'string', 'max:120'],
            'homepage.categories_section.view_all_label' => ['nullable', 'string', 'max:120'],
            'homepage.categories_section.cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.new_arrivals' => ['nullable', 'array'],
            'homepage.new_arrivals.show' => ['sometimes', 'boolean'],
            'homepage.new_arrivals.title' => ['nullable', 'string', 'max:120'],
            'homepage.new_arrivals.view_all_label' => ['nullable', 'string', 'max:120'],
            'homepage.bestsellers' => ['nullable', 'array'],
            'homepage.bestsellers.show' => ['sometimes', 'boolean'],
            'homepage.bestsellers.title' => ['nullable', 'string', 'max:120'],
            'homepage.bestsellers.view_all_label' => ['nullable', 'string', 'max:120'],
            'homepage.bestsellers.badge_label' => ['nullable', 'string', 'max:80'],
            'homepage.bestsellers.quick_add_label' => ['nullable', 'string', 'max:80'],
            'homepage.promo_sale' => ['nullable', 'array'],
            'homepage.promo_sale.show' => ['sometimes', 'boolean'],
            'homepage.promo_sale.eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.promo_sale.title' => ['nullable', 'string', 'max:120'],
            'homepage.promo_sale.subtitle' => ['nullable', 'string', 'max:255'],
            'homepage.promo_sale.cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.promo_sale.cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.promo_sale.countdown_ends_at' => ['nullable', 'date'],
            'homepage.promo_collection' => ['nullable', 'array'],
            'homepage.promo_collection.show' => ['sometimes', 'boolean'],
            'homepage.promo_collection.eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.promo_collection.title' => ['nullable', 'string', 'max:120'],
            'homepage.promo_collection.subtitle' => ['nullable', 'string', 'max:255'],
            'homepage.promo_collection.cta_label' => ['nullable', 'string', 'max:80'],
            'homepage.promo_collection.cta_target' => ['nullable', 'string', 'max:80'],
            'homepage.promo_collection.image_url' => ['nullable', 'string', 'max:500'],
            'homepage.footer_trust' => ['nullable', 'array'],
            'homepage.footer_trust.show' => ['sometimes', 'boolean'],
            'homepage.footer_trust.items' => ['nullable', 'array', 'max:6'],
            'homepage.footer_trust.items.*.title' => ['nullable', 'string', 'max:120'],
            'homepage.footer_tagline' => ['nullable', 'string', 'max:255'],
            'homepage.about_text' => ['nullable', 'string', 'max:2000'],
            'homepage.contact_text' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function mergeOnlineStoreSettingBooleans(): void
    {
        foreach ([
            'is_published',
            'accept_pickup',
            'accept_delivery',
            'accept_dine_in',
            'auto_accept_orders',
        ] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        // Nested boolean merge
        $homepage = $this->input('homepage');
        if (is_array($homepage)) {
            $boolPaths = [
                'show_announcement',
                'hero.show_social_proof',
                'hero.show_float_cards',
                'hero.autoplay',
                'trust.show',
                'categories_section.show',
                'new_arrivals.show',
                'bestsellers.show',
                'promo_sale.show',
                'promo_collection.show',
                'footer_trust.show',
            ];
            foreach ($boolPaths as $path) {
                if (data_get($homepage, $path) !== null) {
                    data_set($homepage, $path, filter_var(data_get($homepage, $path), FILTER_VALIDATE_BOOLEAN));
                }
            }

            $slides = data_get($homepage, 'hero.slides');
            if (is_array($slides)) {
                foreach ($slides as $index => $slide) {
                    if (! is_array($slide)) {
                        continue;
                    }
                    if (array_key_exists('remove_image', $slide)) {
                        $slides[$index]['remove_image'] = filter_var($slide['remove_image'], FILTER_VALIDATE_BOOLEAN);
                    }
                }
                data_set($homepage, 'hero.slides', $slides);
            }

            $this->merge(['homepage' => $homepage]);
        }

        foreach (['tagline', 'description', 'logo', 'hero_image', 'announcement', 'support_phone', 'support_email', 'seo_title', 'seo_description', 'free_delivery_threshold', 'orders_open_at', 'orders_close_at'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
