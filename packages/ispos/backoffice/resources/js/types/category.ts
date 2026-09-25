import type { AuditableUser } from '@/types';

export interface CategoryRecord {
    id: string;
    uuid: string;
    company_id: string;
    parent_id: string | null;
    category_code: string;
    name: string;
    description: string | null;
    sort_order: number;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string; company_code?: string; display_name?: string | null };
    parent?: { id: string; name: string; category_code: string } | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface CategoryFormData {
    company_id: string;
    parent_id: string;
    category_code: string;
    name: string;
    description: string;
    sort_order: string;
    status: string;
}

export function defaultCategoryForm(category?: Partial<CategoryRecord> | null, companyId = ''): CategoryFormData {
    return {
        company_id: category?.company_id ?? companyId,
        parent_id: category?.parent_id ?? '',
        category_code: category?.category_code ?? '',
        name: category?.name ?? '',
        description: category?.description ?? '',
        sort_order: category?.sort_order != null ? String(category.sort_order) : '0',
        status: category?.status ?? 'active',
    };
}
