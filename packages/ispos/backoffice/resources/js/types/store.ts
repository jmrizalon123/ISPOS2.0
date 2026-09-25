import type { AuditableUser } from '@/types';

export interface StoreRecord {
    id: string;
    uuid: string;
    company_id: string;
    store_code: string;
    store_name: string;
    legal_name: string | null;
    store_type: string | null;
    store_category: string | null;
    description: string | null;
    branch_code: string | null;
    tin: string | null;
    bir_registration_no: string | null;
    business_permit_no: string | null;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    barangay: string | null;
    city: string | null;
    province: string | null;
    region: string | null;
    country: string;
    postal_code: string | null;
    latitude: number | string | null;
    longitude: number | string | null;
    manager_id: string | null;
    warehouse_id: string | null;
    price_group_id: string | null;
    sales_plan_id: string | null;
    default_tax_rate: number | string | null;
    currency: string;
    timezone: string;
    opening_time: string | null;
    closing_time: string | null;
    operating_days: string[] | null;
    is_24_hours: boolean;
    enable_pos: boolean;
    enable_inventory: boolean;
    enable_online_ordering: boolean;
    enable_delivery: boolean;
    enable_pickup: boolean;
    enable_dine_in: boolean;
    enable_takeaway: boolean;
    receipt_header: string | null;
    receipt_footer: string | null;
    logo: string | null;
    logo_url?: string | null;
    status: string;
    is_active: boolean;
    opened_at: string | null;
    closed_at: string | null;
    created_by: string | null;
    updated_by: string | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    company?: { id: string; name: string; company_code: string; display_name?: string | null };
    sales_plan?: { id: string; name: string; plan_code?: string } | null;
}

export interface StoreFormData {
    company_id: string;
    store_code: string;
    store_name: string;
    legal_name: string;
    store_type: string;
    store_category: string;
    description: string;
    branch_code: string;
    tin: string;
    bir_registration_no: string;
    business_permit_no: string;
    email: string;
    phone: string;
    mobile: string;
    address_line_1: string;
    address_line_2: string;
    barangay: string;
    city: string;
    province: string;
    region: string;
    country: string;
    postal_code: string;
    latitude: string;
    longitude: string;
    manager_id: string;
    warehouse_id: string;
    price_group_id: string;
    sales_plan_id: string;
    default_tax_rate: string;
    currency: string;
    timezone: string;
    opening_time: string;
    closing_time: string;
    operating_days: string[];
    is_24_hours: boolean;
    enable_pos: boolean;
    enable_inventory: boolean;
    enable_online_ordering: boolean;
    enable_delivery: boolean;
    enable_pickup: boolean;
    enable_dine_in: boolean;
    enable_takeaway: boolean;
    receipt_header: string;
    receipt_footer: string;
    logo: File | null;
    remove_logo: boolean;
    status: string;
    is_active: boolean;
    opened_at: string;
    closed_at: string;
}

const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as const;

function str(value: string | null | undefined): string {
    return value ?? '';
}

function timeValue(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value.length >= 5 ? value.slice(0, 5) : value;
}

export function defaultStoreForm(store?: Partial<StoreRecord> | null, companyId = ''): StoreFormData {
    const days = store?.operating_days?.filter((day) => WEEKDAYS.includes(day as (typeof WEEKDAYS)[number])) ?? [
        ...WEEKDAYS,
    ];

    return {
        company_id: store?.company_id ?? companyId,
        store_code: store?.store_code ?? '',
        store_name: store?.store_name ?? '',
        legal_name: str(store?.legal_name),
        store_type: str(store?.store_type),
        store_category: str(store?.store_category),
        description: str(store?.description),
        branch_code: str(store?.branch_code),
        tin: str(store?.tin),
        bir_registration_no: str(store?.bir_registration_no),
        business_permit_no: str(store?.business_permit_no),
        email: str(store?.email),
        phone: str(store?.phone),
        mobile: str(store?.mobile),
        address_line_1: str(store?.address_line_1),
        address_line_2: str(store?.address_line_2),
        barangay: str(store?.barangay),
        city: str(store?.city),
        province: str(store?.province),
        region: str(store?.region),
        country: store?.country ?? 'PH',
        postal_code: str(store?.postal_code),
        latitude: store?.latitude != null ? String(store.latitude) : '',
        longitude: store?.longitude != null ? String(store.longitude) : '',
        manager_id: str(store?.manager_id),
        warehouse_id: str(store?.warehouse_id),
        price_group_id: str(store?.price_group_id),
        sales_plan_id: str(store?.sales_plan_id),
        default_tax_rate: store?.default_tax_rate != null ? String(store.default_tax_rate) : '',
        currency: store?.currency ?? 'PHP',
        timezone: store?.timezone ?? 'Asia/Manila',
        opening_time: timeValue(store?.opening_time),
        closing_time: timeValue(store?.closing_time),
        operating_days: [...days],
        is_24_hours: store?.is_24_hours ?? false,
        enable_pos: store?.enable_pos ?? true,
        enable_inventory: store?.enable_inventory ?? true,
        enable_online_ordering: store?.enable_online_ordering ?? false,
        enable_delivery: store?.enable_delivery ?? false,
        enable_pickup: store?.enable_pickup ?? false,
        enable_dine_in: store?.enable_dine_in ?? false,
        enable_takeaway: store?.enable_takeaway ?? false,
        receipt_header: str(store?.receipt_header),
        receipt_footer: str(store?.receipt_footer),
        logo: null,
        remove_logo: false,
        status: store?.status ?? 'active',
        is_active: store?.is_active ?? true,
        opened_at: store?.opened_at?.slice(0, 10) ?? '',
        closed_at: store?.closed_at?.slice(0, 10) ?? '',
    };
}

export const storeOperatingDays = WEEKDAYS;
