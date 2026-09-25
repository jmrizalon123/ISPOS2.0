import type { AuditableUser } from '@/types';

export interface SupplierRecord {
    id: string;
    company_id: string;
    supplier_code: string;
    name: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    payment_terms: string | null;
    notes: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface SupplierFormData {
    company_id: string;
    supplier_code: string;
    name: string;
    contact_name: string;
    email: string;
    phone: string;
    address: string;
    payment_terms: string;
    notes: string;
    status: string;
}

export function defaultSupplierForm(supplier?: Partial<SupplierRecord> | null, companyId = ''): SupplierFormData {
    return {
        company_id: supplier?.company_id ?? companyId,
        supplier_code: supplier?.supplier_code ?? '',
        name: supplier?.name ?? '',
        contact_name: supplier?.contact_name ?? '',
        email: supplier?.email ?? '',
        phone: supplier?.phone ?? '',
        address: supplier?.address ?? '',
        payment_terms: supplier?.payment_terms ?? '',
        notes: supplier?.notes ?? '',
        status: supplier?.status ?? 'active',
    };
}
