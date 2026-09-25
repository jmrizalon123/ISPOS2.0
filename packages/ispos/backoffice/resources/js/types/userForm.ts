export type UserBaseType = 'head_office' | 'store' | 'warehouse';

export type UserFormData = {
    employee_id: string;
    username: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    suffix: string;
    display_name: string;
    avatar: File | null;
    remove_avatar: boolean;
    name: string;
    company_id: string;
    department_id: string;
    position_id: string;
    base_type: UserBaseType;
    default_store_id: string;
    default_warehouse_id: string;
    status: string;
    is_active: boolean;
    is_locked: boolean;
    language: string;
    timezone: string;
    roles: string[];
    store_ids: string[];
};

export type UserRecord = Omit<UserFormData, 'avatar' | 'remove_avatar'> & {
    id: string;
    uuid: string | null;
    avatar: string | null;
    avatar_url: string | null;
    email_verified_at: string | null;
    password_changed_at: string | null;
    two_factor_enabled: boolean;
    failed_login_attempts: number;
    locked_at: string | null;
    last_login_at: string | null;
    last_login_ip: string | null;
    last_activity_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    creator?: { id: string; name: string } | null;
    updater?: { id: string; name: string } | null;
};

function str(value: unknown): string {
    return value == null ? '' : String(value);
}

function bool(value: unknown, fallback = false): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (value === 1 || value === '1') {
        return true;
    }

    if (value === 0 || value === '0') {
        return false;
    }

    return fallback;
}

export function defaultUserForm(user: Partial<UserRecord> | null, fallbackCompanyId = ''): UserFormData {
    return {
        employee_id: str(user?.employee_id),
        username: str(user?.username),
        email: str(user?.email),
        phone: str(user?.phone),
        password: '',
        password_confirmation: '',
        first_name: str(user?.first_name),
        middle_name: str(user?.middle_name),
        last_name: str(user?.last_name),
        suffix: str(user?.suffix),
        display_name: str(user?.display_name),
        avatar: null,
        remove_avatar: false,
        name: str(user?.name),
        company_id: str(user?.company_id || fallbackCompanyId),
        department_id: str(user?.department_id),
        position_id: str(user?.position_id),
        base_type: (user?.base_type as UserBaseType) ?? 'store',
        default_store_id: str(user?.default_store_id),
        default_warehouse_id: str(user?.default_warehouse_id),
        status: str(user?.status || 'active'),
        is_active: user?.is_active == null ? user?.status !== 'inactive' : bool(user.is_active, true),
        is_locked: bool(user?.is_locked),
        language: str(user?.language || 'en'),
        timezone: str(user?.timezone || 'Asia/Manila'),
        roles: user?.roles ?? [],
        store_ids: user?.store_ids ?? [],
    };
}
