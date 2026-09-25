import type { AuditableUser } from '@/types';

export interface ChartOfAccountRecord {
    id: string;
    company_id: string;
    account_code: string;
    account_name: string;
    account_type: string;
    normal_balance: string;
    is_system: boolean;
    status: string;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface ChartOfAccountFormData {
    company_id: string;
    account_code: string;
    account_name: string;
    account_type: string;
    status: string;
}

export function defaultChartOfAccountForm(account?: Partial<ChartOfAccountRecord> | null, companyId = ''): ChartOfAccountFormData {
    return {
        company_id: account?.company_id ?? companyId,
        account_code: account?.account_code ?? '',
        account_name: account?.account_name ?? '',
        account_type: account?.account_type ?? 'asset',
        status: account?.status ?? 'active',
    };
}
