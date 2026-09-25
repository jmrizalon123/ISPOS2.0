import { accentPresets, type AccentPreset } from '@/Composables/appearancePresets';
import type { ThemePresetId } from '@/Composables/themePresets';
import type { ThemeMode } from '@/Composables/useTheme.types';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';

export type AccentSource = 'preset' | 'custom';
export type SidebarStyle = 'light' | 'dark' | 'accent' | 'minimal';
export type TopbarStyle = 'default' | 'glass' | 'solid' | 'accent';
export type ColorModeKey = 'light' | 'dark';

const VALID_THEME_PRESETS = new Set<ThemePresetId>([
    'cupertino',
    'sonoma',
    'monterey',
    'graphite',
    'midnight',
    'daybreak',
]);

export interface AppearanceState {
    accentSource: AccentSource;
    accent: AccentPreset;
    customAccentColor: string;
    sidebar: SidebarStyle;
    topbar: TopbarStyle;
    customSidebarColor: string | null;
    customTopbarColor: string | null;
}

export interface ModeAppearanceMap {
    light: AppearanceState;
    dark: AppearanceState;
}

export const appearanceDefaults: AppearanceState = {
    accentSource: 'preset',
    accent: 'blue',
    customAccentColor: '#2563eb',
    sidebar: 'light',
    topbar: 'default',
    customSidebarColor: null,
    customTopbarColor: null,
};

export function defaultModeAppearanceMap(): ModeAppearanceMap {
    return {
        light: { ...appearanceDefaults },
        dark: { ...appearanceDefaults, sidebar: 'dark' },
    };
}

export interface UserPreferences {
    theme: ThemeMode;
    themePreset?: ThemePresetId | null;
    appearance: ModeAppearanceMap;
}

export function defaultUserPreferences(): UserPreferences {
    return {
        theme: 'system',
        themePreset: null,
        appearance: defaultModeAppearanceMap(),
    };
}

function normalizeAppearanceState(raw: Partial<AppearanceState> | undefined, fallback: AppearanceState): AppearanceState {
    return {
        accentSource: raw?.accentSource === 'custom' ? 'custom' : 'preset',
        accent: raw?.accent && raw.accent in accentPresets ? raw.accent : fallback.accent,
        customAccentColor: raw?.customAccentColor?.match(/^#[0-9a-fA-F]{6}$/i)
            ? raw.customAccentColor
            : fallback.customAccentColor,
        sidebar: ['light', 'dark', 'accent', 'minimal'].includes(raw?.sidebar ?? '')
            ? (raw!.sidebar as SidebarStyle)
            : fallback.sidebar,
        topbar: ['default', 'glass', 'solid', 'accent'].includes(raw?.topbar ?? '')
            ? (raw!.topbar as TopbarStyle)
            : fallback.topbar,
        customSidebarColor: raw?.customSidebarColor?.match(/^#[0-9a-fA-F]{6}$/i)
            ? raw.customSidebarColor
            : null,
        customTopbarColor: raw?.customTopbarColor?.match(/^#[0-9a-fA-F]{6}$/i)
            ? raw.customTopbarColor
            : null,
    };
}

function normalizeModeAppearanceMap(raw: unknown): ModeAppearanceMap {
    const defaults = defaultModeAppearanceMap();

    if (!raw || typeof raw !== 'object') {
        return defaults;
    }

    const data = raw as Partial<ModeAppearanceMap> & Partial<AppearanceState>;

    if ('accentSource' in data || 'accent' in data) {
        const legacy = normalizeAppearanceState(data, appearanceDefaults);

        return {
            light: { ...legacy },
            dark: { ...legacy },
        };
    }

    return {
        light: normalizeAppearanceState(data.light, defaults.light),
        dark: normalizeAppearanceState(data.dark, defaults.dark),
    };
}

export function normalizeUserPreferences(raw: unknown): UserPreferences {
    const defaults = defaultUserPreferences();

    if (!raw || typeof raw !== 'object') {
        return defaults;
    }

    const data = raw as Partial<UserPreferences>;

    const appearanceRaw = data.appearance;
    const nestedPreset =
        appearanceRaw && typeof appearanceRaw === 'object' && 'themePreset' in appearanceRaw
            ? (appearanceRaw as { themePreset?: unknown }).themePreset
            : null;
    const rawPreset = data.themePreset ?? nestedPreset;

    const themePreset =
        typeof rawPreset === 'string' && VALID_THEME_PRESETS.has(rawPreset as ThemePresetId)
            ? (rawPreset as ThemePresetId)
            : null;

    return {
        theme: data.theme === 'light' || data.theme === 'dark' || data.theme === 'system' ? data.theme : defaults.theme,
        themePreset,
        appearance: normalizeModeAppearanceMap(data.appearance),
    };
}

export function cloneUserPreferences(preferences: UserPreferences): UserPreferences {
    return {
        theme: preferences.theme,
        themePreset: preferences.themePreset ?? null,
        appearance: {
            light: { ...preferences.appearance.light },
            dark: { ...preferences.appearance.dark },
        },
    };
}

export function userPreferencesEqual(a: UserPreferences, b: UserPreferences): boolean {
    return JSON.stringify(a) === JSON.stringify(b);
}

export function resolveThemeMode(mode: ThemeMode): ColorModeKey {
    if (mode === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    return mode;
}

export function getEditingModeKey(theme: ThemeMode): ColorModeKey {
    return resolveThemeMode(theme);
}

export function getAppearanceForColorMode(preferences: UserPreferences, colorMode: ColorModeKey): AppearanceState {
    return preferences.appearance[colorMode];
}

export function resolveActiveAppearance(preferences: UserPreferences): AppearanceState {
    return getAppearanceForColorMode(preferences, resolveThemeMode(preferences.theme));
}

export function readSavedUserPreferences(): UserPreferences {
    const page = usePage<PageProps>();
    return normalizeUserPreferences(page.props.auth.user?.preferences);
}
