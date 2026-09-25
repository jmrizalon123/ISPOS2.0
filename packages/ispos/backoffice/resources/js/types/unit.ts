import type { AuditableUser } from '@/types';

export interface UnitRecord {
    id: string;
    uuid: string;
    company_id: string;
    unit_code: string;
    name: string;
    symbol: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface UnitFormData {
    company_id: string;
    unit_code: string;
    name: string;
    symbol: string;
    status: string;
}

export function defaultUnitForm(unit?: Partial<UnitRecord> | null, companyId = ''): UnitFormData {
    return {
        company_id: unit?.company_id ?? companyId,
        unit_code: unit?.unit_code ?? '',
        name: unit?.name ?? '',
        symbol: unit?.symbol ?? '',
        status: unit?.status ?? 'active',
    };
}
