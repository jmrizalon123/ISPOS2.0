import type { ThemeMode } from '@/Composables/useTheme.types';
import type { AppearanceState, UserPreferences } from '@/Composables/userPreferences';
import { accentPresets } from '@/Composables/appearancePresets';

export type ThemePresetId =
    | 'cupertino'
    | 'sonoma'
    | 'monterey'
    | 'graphite'
    | 'midnight'
    | 'daybreak'
    | 'canvas'
    | 'bloom'
    | 'obsidian'
    | 'aurora';

export interface ThemePreset {
    id: ThemePresetId;
    label: string;
    description: string;
    /** CSS gradient for the preset card preview */
    preview: string;
    swatch: string;
    theme: ThemeMode;
    light: AppearanceState;
    dark: AppearanceState;
}

function presetAccentHex(key: keyof typeof accentPresets): string {
    return accentPresets[key].swatch;
}

function state(
    accent: AppearanceState['accent'],
    sidebar: AppearanceState['sidebar'],
    topbar: AppearanceState['topbar'],
): AppearanceState {
    return {
        accentSource: 'preset',
        accent,
        customAccentColor: accentPresets[accent].swatch,
        sidebar,
        topbar,
        customSidebarColor: null,
        customTopbarColor: null,
    };
}

export const themePresets: Record<ThemePresetId, ThemePreset> = {
    cupertino: {
        id: 'cupertino',
        label: 'Cupertino',
        description: 'Classic Apple system look — clean light UI with frosted glass chrome.',
        preview: 'linear-gradient(135deg, #f5f5f7 0%, #e8e8ed 45%, #007aff 100%)',
        swatch: presetAccentHex('blue'),
        theme: 'system',
        light: state('blue', 'light', 'glass'),
        dark: state('blue', 'dark', 'glass'),
    },
    sonoma: {
        id: 'sonoma',
        label: 'Sonoma',
        description: 'macOS Sonoma vibes — soft surfaces, minimal chrome, balanced contrast.',
        preview: 'linear-gradient(135deg, #fafafa 0%, #d2d2d7 50%, #5856d6 100%)',
        swatch: presetAccentHex('indigo'),
        theme: 'system',
        light: state('indigo', 'minimal', 'glass'),
        dark: state('indigo', 'dark', 'glass'),
    },
    monterey: {
        id: 'monterey',
        label: 'Monterey',
        description: 'Refined productivity palette with indigo accents and solid headers.',
        preview: 'linear-gradient(135deg, #eef2ff 0%, #c7d2fe 55%, #4f46e5 100%)',
        swatch: presetAccentHex('indigo'),
        theme: 'light',
        light: state('indigo', 'light', 'default'),
        dark: state('indigo', 'dark', 'default'),
    },
    graphite: {
        id: 'graphite',
        label: 'Graphite',
        description: 'Neutral Apple Pro aesthetic — slate tones, understated and focused.',
        preview: 'linear-gradient(135deg, #f8fafc 0%, #94a3b8 50%, #334155 100%)',
        swatch: presetAccentHex('slate'),
        theme: 'system',
        light: state('slate', 'minimal', 'solid'),
        dark: state('slate', 'dark', 'solid'),
    },
    midnight: {
        id: 'midnight',
        label: 'Midnight',
        description: 'Deep dark mode with violet glow — ideal for low-light environments.',
        preview: 'linear-gradient(135deg, #1c1c1e 0%, #2c2c2e 45%, #7c3aed 100%)',
        swatch: presetAccentHex('violet'),
        theme: 'dark',
        light: state('violet', 'light', 'glass'),
        dark: state('violet', 'dark', 'glass'),
    },
    daybreak: {
        id: 'daybreak',
        label: 'Daybreak',
        description: 'Bright, airy workspace with fresh teal accents and soft panels.',
        preview: 'linear-gradient(135deg, #f0fdfa 0%, #99f6e4 50%, #0f766e 100%)',
        swatch: presetAccentHex('teal'),
        theme: 'light',
        light: state('teal', 'light', 'glass'),
        dark: state('teal', 'dark', 'glass'),
    },
    canvas: {
        id: 'canvas',
        label: 'Canvas',
        description: 'Warm studio light — amber accents on clean white surfaces.',
        preview: 'linear-gradient(135deg, #fffbeb 0%, #fde68a 55%, #d97706 100%)',
        swatch: presetAccentHex('amber'),
        theme: 'light',
        light: state('amber', 'light', 'default'),
        dark: state('amber', 'dark', 'default'),
    },
    bloom: {
        id: 'bloom',
        label: 'Bloom',
        description: 'Soft rose accents with an open, editorial light layout.',
        preview: 'linear-gradient(135deg, #fff1f2 0%, #fecdd3 50%, #e11d48 100%)',
        swatch: presetAccentHex('rose'),
        theme: 'light',
        light: state('rose', 'light', 'glass'),
        dark: state('rose', 'dark', 'glass'),
    },
    obsidian: {
        id: 'obsidian',
        label: 'Obsidian',
        description: 'Pro dark UI — slate chrome with crisp solid headers.',
        preview: 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #64748b 100%)',
        swatch: presetAccentHex('slate'),
        theme: 'dark',
        light: state('slate', 'light', 'solid'),
        dark: state('slate', 'dark', 'solid'),
    },
    aurora: {
        id: 'aurora',
        label: 'Aurora',
        description: 'Deep night mode with emerald glow and glass navigation.',
        preview: 'linear-gradient(135deg, #022c22 0%, #064e3b 45%, #34d399 100%)',
        swatch: presetAccentHex('emerald'),
        theme: 'dark',
        light: state('emerald', 'light', 'glass'),
        dark: state('emerald', 'dark', 'glass'),
    },
};

/** Preset order grouped by color mode preference */
export const themePresetsByColorMode: Record<ThemeMode, ThemePresetId[]> = {
    light: ['monterey', 'daybreak', 'canvas', 'bloom'],
    dark: ['midnight', 'obsidian', 'aurora'],
    system: ['cupertino', 'sonoma', 'graphite'],
};

export const themePresetList = Object.values(themePresets);

export function getThemePresetsForColorMode(theme: ThemeMode): ThemePreset[] {
    return themePresetsByColorMode[theme].map((id) => themePresets[id]);
}

export function themePresetColorModeLabel(theme: ThemeMode): string {
    if (theme === 'system') {
        return 'System';
    }

    return theme === 'dark' ? 'Dark' : 'Light';
}

export function isThemePresetId(value: unknown): value is ThemePresetId {
    return typeof value === 'string' && value in themePresets;
}

export function applyThemePresetToPreferences(
    preferences: UserPreferences,
    presetId: ThemePresetId,
): UserPreferences {
    const preset = themePresets[presetId];

    return {
        theme: preset.theme,
        themePreset: presetId,
        appearance: {
            light: { ...preset.light },
            dark: { ...preset.dark },
        },
    };
}

function appearanceStatesEqual(a: AppearanceState, b: AppearanceState): boolean {
    return (
        a.accentSource === b.accentSource
        && a.accent === b.accent
        && a.customAccentColor.toLowerCase() === b.customAccentColor.toLowerCase()
        && a.sidebar === b.sidebar
        && a.topbar === b.topbar
        && a.customSidebarColor === b.customSidebarColor
        && a.customTopbarColor === b.customTopbarColor
    );
}

export function presetMatchesPreferences(preferences: UserPreferences, preset: ThemePreset): boolean {
    return (
        appearanceStatesEqual(preferences.appearance.light, preset.light)
        && appearanceStatesEqual(preferences.appearance.dark, preset.dark)
        && preferences.theme === preset.theme
    );
}

export function detectThemePreset(preferences: UserPreferences): ThemePresetId | null {
    if (preferences.themePreset && isThemePresetId(preferences.themePreset)) {
        const preset = themePresets[preferences.themePreset];
        if (presetMatchesPreferences(preferences, preset)) {
            return preferences.themePreset;
        }
    }

    for (const preset of themePresetList) {
        if (presetMatchesPreferences(preferences, preset)) {
            return preset.id;
        }
    }

    return null;
}

export function syncThemePreset(preferences: UserPreferences): UserPreferences {
    return {
        ...preferences,
        themePreset: detectThemePreset(preferences),
    };
}
