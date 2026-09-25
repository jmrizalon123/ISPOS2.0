export interface AppearanceState {
    accentSource: 'preset' | 'custom';
    accent: string;
    customAccentColor: string;
    sidebar: string;
    topbar: string;
    customSidebarColor: string | null;
    customTopbarColor: string | null;
}

export interface UserPreferences {
    theme: 'light' | 'dark' | 'system';
    themePreset?: string | null;
    locale?: string;
    appearance: {
        light: AppearanceState;
        dark: AppearanceState;
    };
}

export interface LocaleMeta {
    name: string;
    native: string;
    google: string;
}

export interface TranslationSettings {
    provider: 'builtin' | 'google';
    google_enabled: boolean;
    google_use_widget: boolean;
    has_api_key: boolean;
}

export interface IsposBrandingSettings {
    name: string;
    tagline: string;
    ownership: string;
    copyright: string;
    logo_url: string | null;
    support_email: string;
    support_url: string;
}

export interface IsposBrandingConfig extends IsposBrandingSettings {
    initials: string;
}

export interface TranslationConfig {
    provider: 'builtin' | 'google';
    google: {
        enabled: boolean;
        useWidget: boolean;
        hasApiKey: boolean;
        pageLanguage: string;
        includedLanguages: string;
    };
}

export interface AuditableUser {
    id: string;
    name: string;
}

export interface BackofficeContext {
    scope: 'company' | 'store';
    label: string;
    company_id: string | null;
    store_id: string | null;
    store_name: string | null;
    store_code: string | null;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar_url?: string | null;
    company_id?: string | null;
    preferred_store_id?: string | null;
    default_store_id?: string | null;
    base_type?: 'head_office' | 'store' | 'warehouse' | null;
    email_verified_at?: string | null;
    roles?: string[];
    permissions?: string[];
    preferences?: UserPreferences;
    two_factor_enabled?: boolean;
    company?: Pick<Company, 'id' | 'name' | 'company_code' | 'display_name'> | null;
}

export interface Company {
    id: string;
    company_code: string;
    name: string;
    display_name?: string | null;
    timezone?: string;
    base_currency?: string;
    status: string;
}

export interface Store {
    id: string;
    company_id: string;
    store_code: string;
    store_name: string;
    legal_name?: string | null;
    city?: string | null;
    phone?: string | null;
    status: string;
    company?: Pick<Company, 'id' | 'name' | 'company_code' | 'display_name'>;
}

export interface Register {
    id: string;
    store_id: string;
    code: string;
    name: string;
    status: string;
    store?: Pick<Store, 'id' | 'store_name' | 'store_code' | 'company_id'>;
}

export interface UserRecord {
    id: string;
    company_id: string | null;
    preferred_store_id: string | null;
    name: string;
    email: string;
    status: string;
    company?: Pick<Company, 'id' | 'name'>;
    roles?: Array<{ id: number; name: string }>;
    stores?: Pick<Store, 'id' | 'store_name' | 'store_code'>[];
}

export interface AuditLogRecord {
    id: string;
    action: string;
    module: string;
    record_type: string | null;
    record_id: string | null;
    ip_address: string | null;
    device: string | null;
    created_at: string;
    user?: Pick<UserRecord, 'id' | 'name' | 'email'> | null;
}

export interface LowStockAlertPreview {
    id: string;
    name: string;
    sku: string;
    qty: number | string;
    warning_qty: number | string | null;
    stock_status: 'ok' | 'low' | 'out' | 'not_tracked';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        context?: BackofficeContext | null;
        canSwitchContext?: boolean;
        contextOptions?: {
            can_select_company: boolean;
            company: { id: string; name: string; company_code: string; display_name: string | null } | null;
            stores: Array<{ id: string; store_name: string; store_code: string }>;
        } | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
    app: {
        name: string;
        csrf_token?: string;
        pos_url?: string;
        branding?: IsposBrandingConfig;
    };
    locale?: {
        current: string;
        default: string;
        supported: Record<string, LocaleMeta>;
    };
    translation?: TranslationConfig;
    alerts?: {
        low_stock_count: number;
        low_stock_items: LowStockAlertPreview[];
    };
};

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
