<?php

namespace App\Domains\OnlineStore\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontHomepageDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            'show_announcement' => true,
            'announcement_items' => [
                'Free worldwide shipping on qualifying orders',
                'Secure checkout',
                'Limited-time flash deals',
            ],
            'search_placeholder' => 'Search products…',
            'nav_links' => [
                ['label' => 'Home', 'target' => 'home'],
                ['label' => 'Shop', 'target' => 'shop'],
                ['label' => 'New Arrivals', 'target' => 'arrivals'],
                ['label' => 'Best Sellers', 'target' => 'bestsellers'],
                ['label' => 'Categories', 'target' => 'categories'],
                ['label' => 'About', 'target' => 'about'],
                ['label' => 'Contact', 'target' => 'contact'],
            ],
            'hero' => [
                'eyebrow' => 'Trending now',
                'headline' => 'Discover Products You’ll Love',
                'subheadline' => 'Shop the latest trending products curated for modern lifestyles.',
                'primary_cta_label' => 'Shop Now',
                'primary_cta_target' => 'shop',
                'secondary_cta_label' => 'Explore Collection',
                'secondary_cta_target' => 'categories',
                'social_proof' => 'Loved by shoppers worldwide',
                'show_social_proof' => true,
                'show_float_cards' => true,
                'autoplay' => true,
                'interval_ms' => 5500,
                'slides' => [],
            ],
            'trust' => [
                'show' => true,
                'items' => [
                    ['title' => 'Free Shipping', 'subtitle' => 'On qualifying orders'],
                    ['title' => 'Secure Payments', 'subtitle' => 'Protected checkout'],
                    ['title' => 'Easy Returns', 'subtitle' => 'Hassle-free support'],
                    ['title' => '24/7 Support', 'subtitle' => 'We’re here to help'],
                ],
            ],
            'categories_section' => [
                'show' => true,
                'title' => 'Shop by Categories',
                'view_all_label' => 'View All Categories',
                'cta_label' => 'Shop Now',
            ],
            'new_arrivals' => [
                'show' => true,
                'title' => 'New Arrivals',
                'view_all_label' => 'View All New Arrivals',
            ],
            'bestsellers' => [
                'show' => true,
                'title' => 'Best Sellers',
                'view_all_label' => 'View All Best Sellers',
                'badge_label' => 'Bestseller',
                'quick_add_label' => 'Quick Add',
            ],
            'promo_sale' => [
                'show' => true,
                'eyebrow' => 'Limited Time',
                'title' => 'Flash Sale',
                'subtitle' => 'Up to 70% Off',
                'cta_label' => 'Shop Sale Now',
                'cta_target' => 'shop',
                'countdown_ends_at' => '',
            ],
            'promo_collection' => [
                'show' => true,
                'eyebrow' => 'New Collection',
                'title' => 'Summer Collection',
                'subtitle' => 'Fresh looks for the season ahead.',
                'cta_label' => 'Shop Collection',
                'cta_target' => 'arrivals',
                'image_url' => '',
            ],
            'footer_trust' => [
                'show' => true,
                'items' => [
                    ['title' => 'Premium Quality'],
                    ['title' => 'Fast Delivery'],
                    ['title' => 'Secure Checkout'],
                    ['title' => 'Customer Satisfaction'],
                ],
            ],
            'footer_tagline' => '',
            'about_text' => 'Quality products, clear pricing, and checkout in minutes.',
            'contact_text' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptySlide(): array
    {
        return [
            'image' => '',
            'eyebrow' => '',
            'headline' => '',
            'subheadline' => '',
            'primary_cta_label' => '',
            'primary_cta_target' => 'shop',
            'secondary_cta_label' => '',
            'secondary_cta_target' => 'categories',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    public static function merge(?array $stored): array
    {
        $merged = array_replace_recursive(self::all(), is_array($stored) ? $stored : []);

        if (isset($stored['hero']['slides']) && is_array($stored['hero']['slides'])) {
            $merged['hero']['slides'] = array_values(array_map(
                fn ($slide) => array_merge(self::emptySlide(), is_array($slide) ? $slide : []),
                $stored['hero']['slides'],
            ));
        } else {
            $merged['hero']['slides'] = [];
        }

        return self::hydrateMediaUrls($merged);
    }

    /**
     * @param  array<string, mixed>  $homepage
     * @return array<string, mixed>
     */
    public static function hydrateMediaUrls(array $homepage): array
    {
        $slides = $homepage['hero']['slides'] ?? [];
        if (! is_array($slides)) {
            return $homepage;
        }

        foreach ($slides as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }
            $homepage['hero']['slides'][$index]['image_url'] = self::publicUrl($slide['image'] ?? null);
        }

        return $homepage;
    }

    public static function publicUrl(mixed $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
