import type { AccentTokens } from '@/Composables/appearancePresets.types';
import type { AppearanceState } from '@/Composables/userPreferences';
import { accentPresets } from '@/Composables/appearancePresets';

export type Rgb = [number, number, number];

export function hexToRgb(hex: string): Rgb | null {
    const normalized = hex.replace('#', '').trim();
    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return null;
    }

    return [
        parseInt(normalized.slice(0, 2), 16),
        parseInt(normalized.slice(2, 4), 16),
        parseInt(normalized.slice(4, 6), 16),
    ];
}

export function rgbToHex([r, g, b]: Rgb): string {
    return `#${[r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('')}`;
}

export function rgbString([r, g, b]: Rgb): string {
    return `${Math.round(r)} ${Math.round(g)} ${Math.round(b)}`;
}

export function clamp(value: number, min = 0, max = 255): number {
    return Math.min(max, Math.max(min, value));
}

export function mixRgb(a: Rgb, b: Rgb, weight: number): Rgb {
    return [
        clamp(a[0] * (1 - weight) + b[0] * weight),
        clamp(a[1] * (1 - weight) + b[1] * weight),
        clamp(a[2] * (1 - weight) + b[2] * weight),
    ];
}

export function lighten([r, g, b]: Rgb, amount: number): Rgb {
    return mixRgb([r, g, b], [255, 255, 255], amount);
}

export function darken([r, g, b]: Rgb, amount: number): Rgb {
    return mixRgb([r, g, b], [0, 0, 0], amount);
}

export function tokensFromHex(hex: string, dark: boolean): AccentTokens | null {
    const rgb = hexToRgb(hex);
    if (!rgb) {
        return null;
    }

    if (dark) {
        const accent = lighten(rgb, 0.35);
        return {
            accent: rgbString(accent),
            accentHover: rgbString(lighten(rgb, 0.5)),
            accentSoft: rgbString(mixRgb(rgb, [15, 23, 42], 0.88)),
            accentMuted: rgbString(mixRgb(rgb, [24, 24, 27], 0.75)),
        };
    }

    return {
        accent: rgbString(darken(rgb, 0.08)),
        accentHover: rgbString(lighten(rgb, 0.12)),
        accentSoft: rgbString(mixRgb(rgb, [255, 255, 255], 0.9)),
        accentMuted: rgbString(mixRgb(rgb, [255, 255, 255], 0.72)),
    };
}

export function rgbStringToHex(value: string): string | null {
    const parts = value.split(/\s+/).map(Number);

    if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
        return null;
    }

    return rgbToHex(parts as Rgb);
}

export function getAccentTokensForMode(state: AppearanceState, resolvedMode: 'light' | 'dark'): AccentTokens | null {
    if (state.accentSource === 'custom') {
        return tokensFromHex(state.customAccentColor, resolvedMode === 'dark');
    }

    const preset = accentPresets[state.accent];
    return resolvedMode === 'dark' ? preset.dark : preset.light;
}

export function getAccentHexForMode(state: AppearanceState, resolvedMode: 'light' | 'dark'): string {
    const tokens = getAccentTokensForMode(state, resolvedMode);

    if (tokens) {
        return rgbStringToHex(tokens.accent) ?? state.customAccentColor;
    }

    return state.customAccentColor;
}

/** Minimal copy for inline blade bootstrap script. */
export function tokensFromHexScript(hex: string, dark: boolean): { a: string; h: string; s: string; m: string } | null {
    const tokens = tokensFromHex(hex, dark);
    if (!tokens) {
        return null;
    }

    return {
        a: tokens.accent,
        h: tokens.accentHover,
        s: tokens.accentSoft,
        m: tokens.accentMuted,
    };
}
