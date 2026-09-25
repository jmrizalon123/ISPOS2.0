import type { AuditableUser } from '@/types';

export interface PriceGroupRecord {
    id: string;
    uuid: string;
    company_id: string;
    group_code: string;
    name: string;
    description: string | null;
    is_default: boolean;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface PriceGroupFormData {
    company_id: string;
    group_code: string;
    name: string;
    description: string;
    is_default: boolean;
    status: string;
}

export function defaultPriceGroupForm(group?: Partial<PriceGroupRecord> | null, companyId = ''): PriceGroupFormData {
    return {
        company_id: group?.company_id ?? companyId,
        group_code: group?.group_code ?? '',
        name: group?.name ?? '',
        description: group?.description ?? '',
        is_default: group?.is_default ?? false,
        status: group?.status ?? 'active',
    };
}
