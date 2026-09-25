import type { AuditableUser } from '@/types';

export interface BrandRecord {
    id: string;
    uuid: string;
    company_id: string;
    brand_code: string;
    name: string;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface BrandFormData {
    company_id: string;
    brand_code: string;
    name: string;
    status: string;
}

export function defaultBrandForm(brand?: Partial<BrandRecord> | null, companyId = ''): BrandFormData {
    return {
        company_id: brand?.company_id ?? companyId,
        brand_code: brand?.brand_code ?? '',
        name: brand?.name ?? '',
        status: brand?.status ?? 'active',
    };
}
