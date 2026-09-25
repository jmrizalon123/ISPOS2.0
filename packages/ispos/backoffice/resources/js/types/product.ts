import type { AuditableUser } from '@/types';

export type ProductType = 'retail' | 'menu_item' | 'ingredient';
export type StockStatus = 'ok' | 'low' | 'out' | 'not_tracked';

export interface ProductVariantRow {
    id?: string;
    client_key: string;
    variant_code: string;
    name: string;
    sku: string;
    cost: string;
    selling_price: string;
    sort_order: string;
    status: string;
}

export interface ProductBarcodeRow {
    id?: string;
    barcode: string;
    is_primary: boolean;
    product_variant_id: string;
    variant_client_key: string;
}

export interface ProductPriceRow {
    price_group_id: string;
    price: string;
    product_variant_id: string;
    variant_client_key: string;
}

export interface ProductModifierOptionRow {
    id?: string;
    client_key: string;
    option_code: string;
    name: string;
    price_adjustment: string;
    is_default: boolean;
    sort_order: string;
    status: string;
}

export interface ProductModifierGroupRow {
    id?: string;
    client_key: string;
    group_code: string;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_selections: string;
    max_selections: string;
    sort_order: string;
    status: string;
    options: ProductModifierOptionRow[];
}

export interface ProductImageRow {
    id?: string;
    client_key: string;
    is_default: boolean;
    sort_order: string;
    upload_index?: number;
    url?: string;
    preview_url?: string;
    name?: string;
    size_bytes?: number;
    file?: File;
}

export interface ProductIngredientRow {
    id?: string;
    ingredient_product_id: string;
    quantity: string;
    unit_id: string;
    is_optional: boolean;
    sort_order: string;
    notes: string;
}

export interface ProductComponentRow {
    id?: string;
    component_product_id: string;
    quantity: string;
    unit_id: string;
    is_optional: boolean;
    sort_order: string;
    notes: string;
}

export interface ProductRecord {
    id: string;
    uuid: string;
    company_id: string;
    sales_plan_id: string | null;
    sku: string;
    name: string;
    description: string | null;
    product_type: ProductType;
    category_id: string | null;
    brand_id: string | null;
    unit_id: string | null;
    tax_id: string | null;
    cost: number | string;
    base_price: number | string;
    track_inventory: boolean;
    qty: number | string;
    total_qty?: number | string | null;
    ideal_qty: number | string | null;
    warning_qty: number | string | null;
    stock_status?: StockStatus;
    has_variants: boolean;
    has_modifiers: boolean;
    has_components: boolean;
    image: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string; company_code?: string };
    sales_plan?: { id: string; name: string; plan_code?: string } | null;
    category?: { id: string; name: string; category_code?: string } | null;
    brand?: { id: string; name: string; brand_code?: string } | null;
    unit?: { id: string; name: string; symbol?: string | null } | null;
    tax?: { id: string; name: string; rate?: number | string } | null;
    variants?: Array<{
        id: string;
        variant_code: string;
        name: string;
        sku: string | null;
        cost: number | string;
        selling_price: number | string;
        sort_order: number;
        status: string;
    }>;
    barcodes?: Array<{
        id: string;
        barcode: string;
        is_primary: boolean;
        product_variant_id: string | null;
    }>;
    prices?: Array<{
        id: string;
        price_group_id: string;
        price: number | string;
        product_variant_id: string | null;
    }>;
    modifier_groups?: Array<{
        id: string;
        group_code: string;
        name: string;
        selection_type: 'single' | 'multiple';
        is_required: boolean;
        min_selections: number;
        max_selections: number | null;
        sort_order: number;
        status: string;
        options?: Array<{
            id: string;
            option_code: string;
            name: string;
            price_adjustment: number | string;
            is_default: boolean;
            sort_order: number;
            status: string;
        }>;
    }>;
    ingredients?: Array<{
        id: string;
        ingredient_product_id: string;
        quantity: number | string;
        unit_id: string | null;
        is_optional: boolean;
        sort_order: number;
        notes: string | null;
        ingredient_product?: { id: string; name: string; sku: string; product_type?: string };
        unit?: { id: string; name: string; symbol?: string | null } | null;
    }>;
    components?: Array<{
        id: string;
        component_product_id: string;
        quantity: number | string;
        unit_id: string | null;
        is_optional: boolean;
        sort_order: number;
        notes: string | null;
        component_product?: { id: string; name: string; sku: string; product_type?: string };
        unit?: { id: string; name: string; symbol?: string | null } | null;
    }>;
    images?: Array<{
        id: string;
        path: string;
        url?: string;
        original_name: string | null;
        mime_type: string | null;
        size_bytes: number;
        sort_order: number;
        is_default: boolean;
    }>;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface ProductFormData {
    company_id: string;
    sales_plan_id: string;
    sku: string;
    name: string;
    description: string;
    product_type: ProductType;
    category_id: string;
    brand_id: string;
    unit_id: string;
    tax_id: string;
    cost: string;
    base_price: string;
    track_inventory: boolean;
    qty: string;
    ideal_qty: string;
    warning_qty: string;
    has_variants: boolean;
    has_modifiers: boolean;
    has_components: boolean;
    image: string;
    status: string;
    images: ProductImageRow[];
    image_uploads: File[];
    variants: ProductVariantRow[];
    modifier_groups: ProductModifierGroupRow[];
    components: ProductComponentRow[];
    ingredients: ProductIngredientRow[];
    barcodes: ProductBarcodeRow[];
    prices: ProductPriceRow[];
}

let keySeq = 0;
export function nextClientKey(prefix = 'v'): string {
    keySeq += 1;
    return `${prefix}-${Date.now()}-${keySeq}`;
}

export function emptyVariantRow(): ProductVariantRow {
    return {
        client_key: nextClientKey('var'),
        variant_code: '',
        name: '',
        sku: '',
        cost: '0',
        selling_price: '0',
        sort_order: '0',
        status: 'active',
    };
}

export function emptyBarcodeRow(): ProductBarcodeRow {
    return {
        barcode: '',
        is_primary: false,
        product_variant_id: '',
        variant_client_key: '',
    };
}

export function emptyPriceRow(priceGroupId = ''): ProductPriceRow {
    return {
        price_group_id: priceGroupId,
        price: '0',
        product_variant_id: '',
        variant_client_key: '',
    };
}

export function emptyModifierOptionRow(): ProductModifierOptionRow {
    return {
        client_key: nextClientKey('opt'),
        option_code: '',
        name: '',
        price_adjustment: '0',
        is_default: false,
        sort_order: '0',
        status: 'active',
    };
}

export function emptyModifierGroupRow(): ProductModifierGroupRow {
    return {
        client_key: nextClientKey('mod'),
        group_code: '',
        name: '',
        selection_type: 'single',
        is_required: false,
        min_selections: '0',
        max_selections: '',
        sort_order: '0',
        status: 'active',
        options: [],
    };
}

export function serializeProductImages(images: ProductImageRow[]): {
    images: Array<Record<string, unknown>>;
    image_uploads: File[];
} {
    const image_uploads: File[] = [];

    const payload = images.map((row, index) => {
        if (row.file) {
            const upload_index = image_uploads.length;
            image_uploads.push(row.file);

            return {
                client_key: row.client_key,
                upload_index,
                is_default: row.is_default,
                sort_order: index,
            };
        }

        return {
            id: row.id,
            is_default: row.is_default,
            sort_order: index,
        };
    });

    return { images: payload, image_uploads };
}

export function emptyIngredientRow(): ProductIngredientRow {
    return {
        ingredient_product_id: '',
        quantity: '1',
        unit_id: '',
        is_optional: false,
        sort_order: '0',
        notes: '',
    };
}

export function emptyComponentRow(): ProductComponentRow {
    return {
        component_product_id: '',
        quantity: '1',
        unit_id: '',
        is_optional: false,
        sort_order: '0',
        notes: '',
    };
}

export function defaultProductForm(product?: Partial<ProductRecord> | null, companyId = ''): ProductFormData {
    const variants =
        product?.variants?.map((v, i) => ({
            id: v.id,
            client_key: nextClientKey('var'),
            variant_code: v.variant_code,
            name: v.name,
            sku: v.sku ?? '',
            cost: String(v.cost ?? 0),
            selling_price: String(v.selling_price ?? 0),
            sort_order: String(v.sort_order ?? i),
            status: v.status ?? 'active',
        })) ?? [];

    const barcodes =
        product?.barcodes?.map((b) => ({
            id: b.id,
            barcode: b.barcode,
            is_primary: !!b.is_primary,
            product_variant_id: b.product_variant_id ?? '',
            variant_client_key: '',
        })) ?? [];

    const prices =
        product?.prices?.map((p) => ({
            price_group_id: p.price_group_id,
            price: String(p.price ?? 0),
            product_variant_id: p.product_variant_id ?? '',
            variant_client_key: '',
        })) ?? [];

    const modifier_groups =
        product?.modifier_groups?.map((g, gi) => ({
            id: g.id,
            client_key: nextClientKey('mod'),
            group_code: g.group_code,
            name: g.name,
            selection_type: g.selection_type ?? 'single',
            is_required: !!g.is_required,
            min_selections: String(g.min_selections ?? 0),
            max_selections: g.max_selections != null ? String(g.max_selections) : '',
            sort_order: String(g.sort_order ?? gi),
            status: g.status ?? 'active',
            options:
                g.options?.map((o, oi) => ({
                    id: o.id,
                    client_key: nextClientKey('opt'),
                    option_code: o.option_code,
                    name: o.name,
                    price_adjustment: String(o.price_adjustment ?? 0),
                    is_default: !!o.is_default,
                    sort_order: String(o.sort_order ?? oi),
                    status: o.status ?? 'active',
                })) ?? [],
        })) ?? [];

    const images =
        product?.images?.map((row, i) => ({
            id: row.id,
            client_key: nextClientKey('img'),
            is_default: !!row.is_default,
            sort_order: String(row.sort_order ?? i),
            url: row.url,
            preview_url: row.url,
            name: row.original_name ?? 'Image',
            size_bytes: row.size_bytes,
        })) ??
        (product?.image
            ? [
                  {
                      client_key: nextClientKey('img'),
                      is_default: true,
                      sort_order: '0',
                      url: product.image,
                      preview_url: product.image,
                      name: 'Current image',
                  },
              ]
            : []);

    const ingredients =
        product?.ingredients?.map((row, i) => ({
            id: row.id,
            ingredient_product_id: row.ingredient_product_id,
            quantity: String(row.quantity ?? 1),
            unit_id: row.unit_id ?? '',
            is_optional: !!row.is_optional,
            sort_order: String(row.sort_order ?? i),
            notes: row.notes ?? '',
        })) ?? [];

    const components =
        product?.components?.map((row, i) => ({
            id: row.id,
            component_product_id: row.component_product_id,
            quantity: String(row.quantity ?? 1),
            unit_id: row.unit_id ?? '',
            is_optional: !!row.is_optional,
            sort_order: String(row.sort_order ?? i),
            notes: row.notes ?? '',
        })) ?? [];

    return {
        company_id: product?.company_id ?? companyId,
        sales_plan_id: product?.sales_plan_id ?? '',
        sku: product?.sku ?? '',
        name: product?.name ?? '',
        description: product?.description ?? '',
        product_type: product?.product_type ?? 'retail',
        category_id: product?.category_id ?? '',
        brand_id: product?.brand_id ?? '',
        unit_id: product?.unit_id ?? '',
        tax_id: product?.tax_id ?? '',
        cost: product?.cost != null ? String(product.cost) : '0',
        base_price: product?.base_price != null ? String(product.base_price) : '0',
        track_inventory: product?.track_inventory ?? true,
        qty: product?.qty != null ? String(product.qty) : '0',
        ideal_qty: product?.ideal_qty != null ? String(product.ideal_qty) : '',
        warning_qty: product?.warning_qty != null ? String(product.warning_qty) : '',
        has_variants: product?.has_variants ?? false,
        has_modifiers: product?.has_modifiers ?? false,
        has_components: product?.has_components ?? false,
        image: product?.image ?? '',
        status: product?.status ?? 'active',
        images,
        image_uploads: [],
        variants,
        barcodes,
        prices,
        modifier_groups,
        components,
        ingredients,
    };
}

export interface ProductBulkItemRow {
    sku: string;
    name: string;
    description: string;
    cost: string;
    base_price: string;
    barcode: string;
    ideal_qty: string;
    warning_qty: string;
    category_id: string;
    brand_id: string;
    unit_id: string;
    has_variants: boolean;
    has_modifiers: boolean;
    has_components: boolean;
    variants: ProductVariantRow[];
    modifier_groups: ProductModifierGroupRow[];
    components: ProductComponentRow[];
    ingredients: ProductIngredientRow[];
    image_preview: string | null;
    images: ProductImageRow[];
}

export interface ProductBulkCreateFormData {
    company_id: string;
    sales_plan_id: string;
    product_type: ProductType;
    tax_id: string;
    track_inventory: boolean;
    status: string;
    items: ProductBulkItemRow[];
}

export function emptyProductBulkItem(): ProductBulkItemRow {
    return {
        sku: '',
        name: '',
        description: '',
        cost: '0',
        base_price: '0',
        barcode: '',
        ideal_qty: '',
        warning_qty: '',
        category_id: '',
        brand_id: '',
        unit_id: '',
        has_variants: false,
        has_modifiers: false,
        has_components: false,
        variants: [],
        modifier_groups: [],
        components: [],
        ingredients: [],
        image_preview: null,
        images: [],
    };
}

export function defaultProductBulkCreateForm(companyId = ''): ProductBulkCreateFormData {
    return {
        company_id: companyId,
        sales_plan_id: '',
        product_type: 'retail',
        tax_id: '',
        track_inventory: true,
        status: 'active',
        items: [emptyProductBulkItem()],
    };
}
