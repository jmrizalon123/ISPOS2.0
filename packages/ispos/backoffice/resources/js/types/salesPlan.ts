import type { AuditableUser } from '@/types';

export interface SalesPlanStoreOption {
    id: string;
    store_name: string;
    store_code: string;
    company_id: string;
    sales_plan_id: string | null;
    store_category?: string | null;
    sales_plan?: { id: string; name: string; plan_code?: string } | null;
}

export interface SalesPlanRecord {
    id: string;
    uuid: string;
    company_id: string;
    plan_code: string;
    name: string;
    description: string | null;
    status: string;
    stores_count?: number;
    products_count?: number;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string; company_code?: string; display_name?: string | null };
    stores?: SalesPlanStoreOption[];
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface SalesPlanFormData {
    company_id: string;
    plan_code: string;
    name: string;
    description: string;
    status: string;
    store_ids: string[];
}

export function defaultSalesPlanForm(
    salesPlan?: Partial<SalesPlanRecord> | null,
    companyId = '',
): SalesPlanFormData {
    return {
        company_id: salesPlan?.company_id ?? companyId,
        plan_code: salesPlan?.plan_code ?? '',
        name: salesPlan?.name ?? '',
        description: salesPlan?.description ?? '',
        status: salesPlan?.status ?? 'active',
        store_ids: salesPlan?.stores?.map((store) => store.id) ?? [],
    };
}
