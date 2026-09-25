import type { AuditableUser } from '@/types';

export interface TaxRecord {
    id: string;
    uuid: string;
    company_id: string;
    tax_code: string;
    name: string;
    rate: number | string;
    is_inclusive: boolean;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface TaxFormData {
    company_id: string;
    tax_code: string;
    name: string;
    rate: string;
    is_inclusive: boolean;
    status: string;
}

export function defaultTaxForm(tax?: Partial<TaxRecord> | null, companyId = ''): TaxFormData {
    return {
        company_id: tax?.company_id ?? companyId,
        tax_code: tax?.tax_code ?? '',
        name: tax?.name ?? '',
        rate: tax?.rate != null ? String(tax.rate) : '12',
        is_inclusive: tax?.is_inclusive ?? false,
        status: tax?.status ?? 'active',
    };
}
