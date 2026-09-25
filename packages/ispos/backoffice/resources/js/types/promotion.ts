import type { AuditableUser } from '@/types';

export interface PromotionRecord {
    id: string;
    company_id: string;
    promo_code: string;
    name: string;
    promo_type: 'percent_off' | 'fixed_amount';
    discount_value: string;
    min_purchase_amount: string | null;
    applies_to: 'all' | 'products' | 'categories';
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    usage_limit: number | null;
    usage_count: number;
    company?: { id: string; name: string };
    products?: Array<{ id: string; name: string; sku: string }>;
    categories?: Array<{ id: string; name: string; category_code: string }>;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
}

export interface PromotionFormData {
    company_id: string;
    promo_code: string;
    name: string;
    promo_type: string;
    discount_value: string | number;
    min_purchase_amount: string | number;
    applies_to: string;
    starts_at: string;
    ends_at: string;
    status: string;
    usage_limit: string | number;
    product_ids: string[];
    category_ids: string[];
}

export function defaultPromotionForm(promotion: PromotionRecord | null, companyId: string): PromotionFormData {
    return {
        company_id: promotion?.company_id ?? companyId,
        promo_code: promotion?.promo_code ?? '',
        name: promotion?.name ?? '',
        promo_type: promotion?.promo_type ?? 'percent_off',
        discount_value: promotion?.discount_value ?? 10,
        min_purchase_amount: promotion?.min_purchase_amount ?? '',
        applies_to: promotion?.applies_to ?? 'all',
        starts_at: promotion?.starts_at ? promotion.starts_at.slice(0, 16) : '',
        ends_at: promotion?.ends_at ? promotion.ends_at.slice(0, 16) : '',
        status: promotion?.status ?? 'draft',
        usage_limit: promotion?.usage_limit ?? '',
        product_ids: promotion?.products?.map((p) => p.id) ?? [],
        category_ids: promotion?.categories?.map((c) => c.id) ?? [],
    };
}
