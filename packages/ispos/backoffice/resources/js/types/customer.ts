import type { AuditableUser } from '@/types';

export interface CustomerRecord {
    id: string;
    uuid: string;
    company_id: string;
    customer_code: string;
    first_name: string;
    last_name: string | null;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    birth_date: string | null;
    address_line_1: string | null;
    city: string | null;
    province: string | null;
    postal_code: string | null;
    price_group_id: string | null;
    loyalty_program_id: string | null;
    loyalty_points: string;
    notes: string | null;
    status: string;
    company?: { id: string; name: string };
    loyalty_program?: { id: string; name: string; program_code: string } | null;
    price_group?: { id: string; name: string; group_code: string } | null;
    memberships?: Array<{
        id: string;
        status: string;
        started_at: string;
        expires_at: string | null;
        membership_plan?: { id: string; name: string; plan_code: string };
    }>;
    loyalty_transactions?: Array<{
        id: string;
        transaction_type: string;
        points_delta: string;
        balance_after: string;
        notes: string | null;
        created_at: string;
        creator?: AuditableUser | null;
    }>;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
}

export interface CustomerFormData {
    company_id: string;
    customer_code: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    mobile: string;
    birth_date: string;
    address_line_1: string;
    city: string;
    province: string;
    postal_code: string;
    price_group_id: string;
    loyalty_program_id: string;
    notes: string;
    status: string;
}

export function defaultCustomerForm(customer: CustomerRecord | null, companyId: string): CustomerFormData {
    return {
        company_id: customer?.company_id ?? companyId,
        customer_code: customer?.customer_code ?? '',
        first_name: customer?.first_name ?? '',
        last_name: customer?.last_name ?? '',
        email: customer?.email ?? '',
        phone: customer?.phone ?? '',
        mobile: customer?.mobile ?? '',
        birth_date: customer?.birth_date ?? '',
        address_line_1: customer?.address_line_1 ?? '',
        city: customer?.city ?? '',
        province: customer?.province ?? '',
        postal_code: customer?.postal_code ?? '',
        price_group_id: customer?.price_group_id ?? '',
        loyalty_program_id: customer?.loyalty_program_id ?? '',
        notes: customer?.notes ?? '',
        status: customer?.status ?? 'active',
    };
}
