<?php

namespace App\Support;

use App\Models\Store;
use App\Services\SettingService;

class PosUiResolver
{
    public const LAYOUT_AUTO = 'auto';

    public const LAYOUT_RETAIL = 'retail';

    public const LAYOUT_RESTAURANT = 'restaurant';

    public const LAYOUT_CAFE = 'cafe';

    public const LAYOUT_WHOLESALE = 'wholesale';

    public const LAYOUT_GROCERY = 'grocery';

    public const LAYOUT_PHARMACY = 'pharmacy';

    public const LAYOUT_GENERIC = 'generic';

    public function __construct(protected SettingService $settings) {}

    public function resolve(Store $store, ?string $registerId = null): string
    {
        $override = $this->settings->get(
            'pos.ui_layout',
            self::LAYOUT_AUTO,
            $store->company_id,
            $store->id,
            $registerId,
        );

        if (is_string($override) && $override !== '' && $override !== self::LAYOUT_AUTO) {
            return $this->normalizeLayout($override);
        }

        return $this->layoutForCategory($store->store_category);
    }

    public function layoutForCategory(?string $category): string
    {
        return match ($category) {
            'restaurant' => self::LAYOUT_RESTAURANT,
            'cafe' => self::LAYOUT_CAFE,
            'wholesale' => self::LAYOUT_WHOLESALE,
            'grocery' => self::LAYOUT_GROCERY,
            'pharmacy' => self::LAYOUT_PHARMACY,
            'other' => self::LAYOUT_GENERIC,
            default => self::LAYOUT_RETAIL,
        };
    }

    /** @return list<string> */
    public static function selectableLayouts(): array
    {
        return [
            self::LAYOUT_RETAIL,
            self::LAYOUT_RESTAURANT,
            self::LAYOUT_CAFE,
            self::LAYOUT_WHOLESALE,
            self::LAYOUT_GROCERY,
            self::LAYOUT_PHARMACY,
            self::LAYOUT_GENERIC,
        ];
    }

    /** @return array<string, string> */
    public static function categoryDefaults(): array
    {
        return [
            'retail' => self::LAYOUT_RETAIL,
            'wholesale' => self::LAYOUT_WHOLESALE,
            'restaurant' => self::LAYOUT_RESTAURANT,
            'cafe' => self::LAYOUT_CAFE,
            'grocery' => self::LAYOUT_GROCERY,
            'pharmacy' => self::LAYOUT_PHARMACY,
            'other' => self::LAYOUT_GENERIC,
        ];
    }

    /** @return array<int, array{value: string, label: string, description: string}> */
    public static function layoutOptions(): array
    {
        return [
            ['value' => self::LAYOUT_AUTO, 'label' => 'Auto (from store category)', 'description' => 'Uses the layout mapped to each store\'s category setting.'],
            ['value' => self::LAYOUT_RETAIL, 'label' => 'Retail', 'description' => 'Barcode-first scan rail, center cart table, footer actions.'],
            ['value' => self::LAYOUT_WHOLESALE, 'label' => 'Wholesale', 'description' => 'B2B catalog table + bulk qty order builder.'],
            ['value' => self::LAYOUT_GROCERY, 'label' => 'Grocery', 'description' => 'Aisle sidebar + product tiles + basket cart.'],
            ['value' => self::LAYOUT_PHARMACY, 'label' => 'Pharmacy', 'description' => 'Scan hero + lookup list + patient dispense panel.'],
            ['value' => self::LAYOUT_RESTAURANT, 'label' => 'Restaurant', 'description' => 'Dark menu board + table-service cart panel.'],
            ['value' => self::LAYOUT_CAFE, 'label' => 'Café', 'description' => 'Large quick-pick tiles + compact order ticket.'],
            ['value' => self::LAYOUT_GENERIC, 'label' => 'Generic', 'description' => 'Split product catalog + embedded sale checkout.'],
        ];
    }

    protected function normalizeLayout(string $layout): string
    {
        return in_array($layout, self::selectableLayouts(), true)
            ? $layout
            : self::LAYOUT_RETAIL;
    }
}
