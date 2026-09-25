import type { AuditableUser } from '@/types';

export interface MembershipPlanRecord {
    id: string;
    company_id: string;
    plan_code: string;
    name: string;
    description: string | null;
    price: string | null;
    duration_days: number | null;
    discount_percent: string;
    price_group_id: string | null;
    status: string;
    company?: { id: string; name: string };
    price_group?: { id: string; name: string; group_code: string } | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
}

export interface MembershipPlanFormData {
    company_id: string;
    plan_code: string;
    name: string;
    description: string;
    price: string | number;
    duration_days: string | number;
    discount_percent: string | number;
    price_group_id: string;
    status: string;
}

export function defaultMembershipPlanForm(plan: MembershipPlanRecord | null, companyId: string): MembershipPlanFormData {
    return {
        company_id: plan?.company_id ?? companyId,
        plan_code: plan?.plan_code ?? '',
        name: plan?.name ?? '',
        description: plan?.description ?? '',
        price: plan?.price ?? '',
        duration_days: plan?.duration_days ?? '',
        discount_percent: plan?.discount_percent ?? 0,
        price_group_id: plan?.price_group_id ?? '',
        status: plan?.status ?? 'active',
    };
}
