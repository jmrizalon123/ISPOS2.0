export type PosUiLayout =
    | 'retail'
    | 'restaurant'
    | 'cafe'
    | 'wholesale'
    | 'grocery'
    | 'pharmacy'
    | 'generic';

export type PosUiLayoutOverride = PosUiLayout | 'auto';

/** @deprecated Use PosUiLayout */
export type PosUiMode = PosUiLayout;

const CATEGORY_DEFAULTS: Record<string, PosUiLayout> = {
    retail: 'retail',
    wholesale: 'wholesale',
    restaurant: 'restaurant',
    cafe: 'cafe',
    grocery: 'grocery',
    pharmacy: 'pharmacy',
    other: 'generic',
};

export const POS_UI_LAYOUT_OPTIONS: Array<{
    value: PosUiLayoutOverride;
    label: string;
    description: string;
}> = [
    { value: 'auto', label: 'Auto (from store category)', description: 'Follows the store category mapping.' },
    {
        value: 'retail',
        label: 'Retail',
        description: 'Scan rail + center cart table + footer action bar (barcode-first checkout).',
    },
    {
        value: 'wholesale',
        label: 'Wholesale',
        description: 'B2B catalog table on the left, bulk qty order builder on the right.',
    },
    {
        value: 'grocery',
        label: 'Grocery',
        description: 'Vertical aisle nav, product tile grid, and basket sidebar.',
    },
    {
        value: 'pharmacy',
        label: 'Pharmacy',
        description: 'Full-width scan hero, lookup list, and patient dispense panel.',
    },
    {
        value: 'restaurant',
        label: 'Restaurant',
        description: 'Dark menu board with table-service cart panel.',
    },
    {
        value: 'cafe',
        label: 'Café',
        description: 'Large quick-pick tiles with compact order ticket sidebar.',
    },
    {
        value: 'generic',
        label: 'Generic',
        description: 'Split catalog cards and embedded sale table for mixed stores.',
    },
];

export function layoutForCategory(storeCategory: string | null | undefined): PosUiLayout {
    if (storeCategory && storeCategory in CATEGORY_DEFAULTS) {
        return CATEGORY_DEFAULTS[storeCategory];
    }

    return 'retail';
}

export function resolvePosUiLayout(
    storeCategory: string | null | undefined,
    override?: PosUiLayoutOverride | null,
): PosUiLayout {
    if (override && override !== 'auto') {
        return override;
    }

    return layoutForCategory(storeCategory);
}

export function usesMenuLayout(layout: PosUiLayout): boolean {
    return layout === 'restaurant' || layout === 'cafe';
}

export function usesRailLayout(layout: PosUiLayout): boolean {
    return layout === 'retail';
}

export function layoutUsesFooter(layout: PosUiLayout): boolean {
    return layout === 'retail';
}

export function layoutUsesHotkeys(layout: PosUiLayout): boolean {
    return layout === 'retail';
}

export function layoutLabel(layout: PosUiLayout): string {
    return POS_UI_LAYOUT_OPTIONS.find((option) => option.value === layout)?.label ?? 'Retail';
}

export function layoutScanTitle(layout: PosUiLayout): string {
    switch (layout) {
        case 'wholesale':
            return 'Wholesale scan';
        case 'grocery':
            return 'Grocery scan';
        case 'pharmacy':
            return 'Pharmacy scan';
        case 'generic':
            return 'Ready to sell';
        default:
            return 'Ready to scan';
    }
}

export function layoutScanHint(layout: PosUiLayout): string {
    switch (layout) {
        case 'wholesale':
            return 'Scan SKU or search — Enter adds line for bulk qty';
        case 'grocery':
            return 'Scan barcode or pick a category below';
        case 'pharmacy':
            return 'Scan product or Rx barcode — Enter to add';
        case 'generic':
            return 'Search or scan to add items to the cart';
        default:
            return 'Scan barcode or type SKU — press Enter to add';
    }
}

/** @deprecated Use resolvePosUiLayout */
export function resolvePosUiMode(storeCategory: string | null | undefined): PosUiLayout {
    return layoutForCategory(storeCategory);
}

/** @deprecated Use usesMenuLayout */
export function isRestaurantPos(storeCategory: string | null | undefined): boolean {
    const layout = layoutForCategory(storeCategory);
    return layout === 'restaurant' || layout === 'cafe';
}
