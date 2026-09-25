import type { AuditableUser } from '@/types';

export interface LoyaltyProgramRecord {
    id: string;
    company_id: string;
    program_code: string;
    name: string;
    earn_rate: string;
    redeem_value_per_point: string;
    min_redeem_points: string;
    is_default: boolean;
    status: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
}

export interface LoyaltyProgramFormData {
    company_id: string;
    program_code: string;
    name: string;
    earn_rate: string | number;
    redeem_value_per_point: string | number;
    min_redeem_points: string | number;
    is_default: boolean;
    status: string;
}

export function defaultLoyaltyProgramForm(program: LoyaltyProgramRecord | null, companyId: string): LoyaltyProgramFormData {
    return {
        company_id: program?.company_id ?? companyId,
        program_code: program?.program_code ?? '',
        name: program?.name ?? '',
        earn_rate: program?.earn_rate ?? 1,
        redeem_value_per_point: program?.redeem_value_per_point ?? 0,
        min_redeem_points: program?.min_redeem_points ?? 0,
        is_default: program?.is_default ?? false,
        status: program?.status ?? 'active',
    };
}
