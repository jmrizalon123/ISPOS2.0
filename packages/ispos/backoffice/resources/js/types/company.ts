import type { AuditableUser } from '@/types';

export interface CompanyRecord {
    id: string;
    uuid: string;
    company_code: string;
    name: string;
    legal_name: string | null;
    display_name: string | null;
    trade_name: string | null;
    company_type: string | null;
    industry: string | null;
    description: string | null;
    tin: string | null;
    bir_registration_no: string | null;
    sec_registration_no: string | null;
    dti_registration_no: string | null;
    business_permit_no: string | null;
    vat_registered: boolean;
    taxpayer_type: string | null;
    default_tax_rate: number | string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    website: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    barangay: string | null;
    city: string | null;
    province: string | null;
    region: string | null;
    country: string;
    postal_code: string | null;
    logo: string | null;
    favicon: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    receipt_header: string | null;
    receipt_footer: string | null;
    base_currency: string;
    currency_symbol: string;
    fiscal_year_start_month: number;
    fiscal_year_start_day: number;
    accounting_method: string;
    default_payment_terms_days: number;
    timezone: string;
    date_format: string;
    time_format: string;
    language: string;
    enable_pos: boolean;
    enable_inventory: boolean;
    enable_accounting: boolean;
    enable_hr: boolean;
    enable_crm: boolean;
    enable_ecommerce: boolean;
    subscription_plan_id: string | null;
    subscription_start_at: string | null;
    subscription_end_at: string | null;
    trial_ends_at: string | null;
    status: string;
    is_active: boolean;
    created_by: string | null;
    updated_by: string | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface CompanyFormData {
    company_code: string;
    name: string;
    legal_name: string;
    display_name: string;
    trade_name: string;
    company_type: string;
    industry: string;
    description: string;
    tin: string;
    bir_registration_no: string;
    sec_registration_no: string;
    dti_registration_no: string;
    business_permit_no: string;
    vat_registered: boolean;
    taxpayer_type: string;
    default_tax_rate: number | string;
    email: string;
    phone: string;
    mobile: string;
    website: string;
    address_line_1: string;
    address_line_2: string;
    barangay: string;
    city: string;
    province: string;
    region: string;
    country: string;
    postal_code: string;
    logo: string;
    favicon: string;
    primary_color: string;
    secondary_color: string;
    receipt_header: string;
    receipt_footer: string;
    base_currency: string;
    currency_symbol: string;
    fiscal_year_start_month: number;
    fiscal_year_start_day: number;
    accounting_method: string;
    default_payment_terms_days: number;
    timezone: string;
    date_format: string;
    time_format: string;
    language: string;
    enable_pos: boolean;
    enable_inventory: boolean;
    enable_accounting: boolean;
    enable_hr: boolean;
    enable_crm: boolean;
    enable_ecommerce: boolean;
    subscription_plan_id: string;
    subscription_start_at: string;
    subscription_end_at: string;
    trial_ends_at: string;
    status: string;
    is_active: boolean;
}

function str(value: string | null | undefined): string {
    return value ?? '';
}

export function defaultCompanyForm(company?: Partial<CompanyRecord> | null): CompanyFormData {
    return {
        company_code: company?.company_code ?? '',
        name: company?.name ?? '',
        legal_name: str(company?.legal_name),
        display_name: str(company?.display_name),
        trade_name: str(company?.trade_name),
        company_type: str(company?.company_type),
        industry: str(company?.industry),
        description: str(company?.description),
        tin: str(company?.tin),
        bir_registration_no: str(company?.bir_registration_no),
        sec_registration_no: str(company?.sec_registration_no),
        dti_registration_no: str(company?.dti_registration_no),
        business_permit_no: str(company?.business_permit_no),
        vat_registered: company?.vat_registered ?? false,
        taxpayer_type: str(company?.taxpayer_type),
        default_tax_rate: company?.default_tax_rate ?? 12,
        email: str(company?.email),
        phone: str(company?.phone),
        mobile: str(company?.mobile),
        website: str(company?.website),
        address_line_1: str(company?.address_line_1),
        address_line_2: str(company?.address_line_2),
        barangay: str(company?.barangay),
        city: str(company?.city),
        province: str(company?.province),
        region: str(company?.region),
        country: company?.country ?? 'PH',
        postal_code: str(company?.postal_code),
        logo: str(company?.logo),
        favicon: str(company?.favicon),
        primary_color: str(company?.primary_color),
        secondary_color: str(company?.secondary_color),
        receipt_header: str(company?.receipt_header),
        receipt_footer: str(company?.receipt_footer),
        base_currency: company?.base_currency ?? 'PHP',
        currency_symbol: company?.currency_symbol ?? '₱',
        fiscal_year_start_month: company?.fiscal_year_start_month ?? 1,
        fiscal_year_start_day: company?.fiscal_year_start_day ?? 1,
        accounting_method: company?.accounting_method ?? 'accrual',
        default_payment_terms_days: company?.default_payment_terms_days ?? 30,
        timezone: company?.timezone ?? 'Asia/Manila',
        date_format: company?.date_format ?? 'Y-m-d',
        time_format: company?.time_format ?? 'H:i',
        language: company?.language ?? 'en',
        enable_pos: company?.enable_pos ?? true,
        enable_inventory: company?.enable_inventory ?? true,
        enable_accounting: company?.enable_accounting ?? false,
        enable_hr: company?.enable_hr ?? false,
        enable_crm: company?.enable_crm ?? false,
        enable_ecommerce: company?.enable_ecommerce ?? false,
        subscription_plan_id: str(company?.subscription_plan_id),
        subscription_start_at: company?.subscription_start_at?.slice(0, 10) ?? '',
        subscription_end_at: company?.subscription_end_at?.slice(0, 10) ?? '',
        trial_ends_at: company?.trial_ends_at?.slice(0, 10) ?? '',
        status: company?.status ?? 'active',
        is_active: company?.is_active ?? true,
    };
}
