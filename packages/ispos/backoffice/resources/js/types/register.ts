import type { AuditableUser } from '@/types';

export interface RegisterRecord {
    id: string;
    uuid: string | null;
    company_id: string | null;
    store_id: string;
    register_code: string;
    register_name: string;
    min: string | null;
    permit_number: string | null;
    device_serial: string | null;
    reset_registration: boolean;
    terminal_code: string | null;
    terminal_name: string | null;
    device_id: string | null;
    device_name: string | null;
    device_type: string | null;
    ip_address: string | null;
    mac_address: string | null;
    printer_id: string | null;
    cash_drawer_id: string | null;
    customer_display_id: string | null;
    kds_station_id: string | null;
    receipt_printer_name: string | null;
    receipt_printer_type: string | null;
    receipt_printer_ip: string | null;
    receipt_printer_port: number | null;
    drawer_open_method: string | null;
    allow_cash_sales: boolean;
    allow_card_sales: boolean;
    allow_gcash_sales: boolean;
    allow_maya_sales: boolean;
    allow_other_payments: boolean;
    allow_discount: boolean;
    allow_void: boolean;
    allow_refund: boolean;
    allow_reprint: boolean;
    allow_price_override: boolean;
    allow_open_drawer: boolean;
    require_cashier_login: boolean;
    require_manager_approval: boolean;
    auto_print_receipt: boolean;
    auto_print_kitchen_order: boolean;
    auto_print_customer_receipt: boolean;
    enable_customer_display: boolean;
    enable_kds: boolean;
    enable_ncs: boolean;
    online_order_enabled: boolean;
    offline_mode_enabled: boolean;
    sync_enabled: boolean;
    last_sync_at: string | null;
    last_z_read_at: string | null;
    current_shift_id: string | null;
    current_cashier_id: string | null;
    status: string;
    is_active: boolean;
    opened_at: string | null;
    closed_at: string | null;
    created_by: string | null;
    updated_by: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    store?: { id: string; store_name: string; store_code: string; company_id: string };
    company?: { id: string; name: string; company_code: string; display_name?: string | null } | null;
    device?: { id: string; name: string } | null;
    current_cashier?: AuditableUser | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
}

export interface RegisterFormData {
    store_id: string;
    register_code: string;
    register_name: string;
    min: string;
    permit_number: string;
    device_serial: string;
    reset_registration: boolean;
    terminal_code: string;
    terminal_name: string;
    device_id: string;
    device_name: string;
    device_type: string;
    ip_address: string;
    mac_address: string;
    printer_id: string;
    cash_drawer_id: string;
    customer_display_id: string;
    kds_station_id: string;
    receipt_printer_name: string;
    receipt_printer_type: string;
    receipt_printer_ip: string;
    receipt_printer_port: string;
    drawer_open_method: string;
    allow_cash_sales: boolean;
    allow_card_sales: boolean;
    allow_gcash_sales: boolean;
    allow_maya_sales: boolean;
    allow_other_payments: boolean;
    allow_discount: boolean;
    allow_void: boolean;
    allow_refund: boolean;
    allow_reprint: boolean;
    allow_price_override: boolean;
    allow_open_drawer: boolean;
    require_cashier_login: boolean;
    require_manager_approval: boolean;
    auto_print_receipt: boolean;
    auto_print_kitchen_order: boolean;
    auto_print_customer_receipt: boolean;
    enable_customer_display: boolean;
    enable_kds: boolean;
    enable_ncs: boolean;
    online_order_enabled: boolean;
    offline_mode_enabled: boolean;
    sync_enabled: boolean;
    status: string;
    is_active: boolean;
}

function str(value: string | null | undefined): string {
    return value ?? '';
}

export function defaultRegisterForm(register?: Partial<RegisterRecord> | null, storeId = ''): RegisterFormData {
    return {
        store_id: register?.store_id ?? storeId,
        register_code: register?.register_code ?? '',
        register_name: register?.register_name ?? '',
        min: str(register?.min),
        permit_number: str(register?.permit_number),
        device_serial: str(register?.device_serial),
        reset_registration: register?.reset_registration ?? false,
        terminal_code: str(register?.terminal_code),
        terminal_name: str(register?.terminal_name),
        device_id: str(register?.device_id),
        device_name: str(register?.device_name),
        device_type: str(register?.device_type),
        ip_address: str(register?.ip_address),
        mac_address: str(register?.mac_address),
        printer_id: str(register?.printer_id),
        cash_drawer_id: str(register?.cash_drawer_id),
        customer_display_id: str(register?.customer_display_id),
        kds_station_id: str(register?.kds_station_id),
        receipt_printer_name: str(register?.receipt_printer_name),
        receipt_printer_type: str(register?.receipt_printer_type),
        receipt_printer_ip: str(register?.receipt_printer_ip),
        receipt_printer_port: register?.receipt_printer_port != null ? String(register.receipt_printer_port) : '',
        drawer_open_method: str(register?.drawer_open_method),
        allow_cash_sales: register?.allow_cash_sales ?? true,
        allow_card_sales: register?.allow_card_sales ?? true,
        allow_gcash_sales: register?.allow_gcash_sales ?? false,
        allow_maya_sales: register?.allow_maya_sales ?? false,
        allow_other_payments: register?.allow_other_payments ?? true,
        allow_discount: register?.allow_discount ?? true,
        allow_void: register?.allow_void ?? true,
        allow_refund: register?.allow_refund ?? true,
        allow_reprint: register?.allow_reprint ?? true,
        allow_price_override: register?.allow_price_override ?? false,
        allow_open_drawer: register?.allow_open_drawer ?? false,
        require_cashier_login: register?.require_cashier_login ?? true,
        require_manager_approval: register?.require_manager_approval ?? false,
        auto_print_receipt: register?.auto_print_receipt ?? true,
        auto_print_kitchen_order: register?.auto_print_kitchen_order ?? false,
        auto_print_customer_receipt: register?.auto_print_customer_receipt ?? false,
        enable_customer_display: register?.enable_customer_display ?? false,
        enable_kds: register?.enable_kds ?? false,
        enable_ncs: register?.enable_ncs ?? false,
        online_order_enabled: register?.online_order_enabled ?? false,
        offline_mode_enabled: register?.offline_mode_enabled ?? true,
        sync_enabled: register?.sync_enabled ?? true,
        status: register?.status ?? 'active',
        is_active: register?.is_active ?? true,
    };
}
