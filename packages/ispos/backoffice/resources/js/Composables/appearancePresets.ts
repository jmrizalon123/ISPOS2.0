import type { AccentTokens } from '@/Composables/appearancePresets.types';

export type { AccentTokens } from '@/Composables/appearancePresets.types';

export type AccentPreset = 'teal' | 'emerald' | 'blue' | 'indigo' | 'violet' | 'rose' | 'amber' | 'slate';
export type SidebarStyle = 'light' | 'dark' | 'accent' | 'minimal';
export type TopbarStyle = 'default' | 'glass' | 'solid' | 'accent';

export const accentPresets: Record<AccentPreset, { label: string; swatch: string; light: AccentTokens; dark: AccentTokens }> = {
    teal: {
        label: 'Teal',
        swatch: '#0f766e',
        light: { accent: '15 118 110', accentHover: '13 148 136', accentSoft: '240 253 250', accentMuted: '204 251 241' },
        dark: { accent: '45 212 191', accentHover: '94 234 212', accentSoft: '19 78 74', accentMuted: '17 94 89' },
    },
    emerald: {
        label: 'Emerald',
        swatch: '#059669',
        light: { accent: '5 150 105', accentHover: '16 185 129', accentSoft: '236 253 245', accentMuted: '167 243 208' },
        dark: { accent: '52 211 153', accentHover: '110 231 183', accentSoft: '6 78 59', accentMuted: '6 95 70' },
    },
    blue: {
        label: 'Blue',
        swatch: '#2563eb',
        light: { accent: '37 99 235', accentHover: '59 130 246', accentSoft: '239 246 255', accentMuted: '191 219 254' },
        dark: { accent: '96 165 250', accentHover: '147 197 253', accentSoft: '30 58 138', accentMuted: '29 78 216' },
    },
    indigo: {
        label: 'Indigo',
        swatch: '#4f46e5',
        light: { accent: '79 70 229', accentHover: '99 102 241', accentSoft: '238 242 255', accentMuted: '199 210 254' },
        dark: { accent: '129 140 248', accentHover: '165 180 252', accentSoft: '49 46 129', accentMuted: '67 56 202' },
    },
    violet: {
        label: 'Violet',
        swatch: '#7c3aed',
        light: { accent: '124 58 237', accentHover: '139 92 246', accentSoft: '245 243 255', accentMuted: '221 214 254' },
        dark: { accent: '167 139 250', accentHover: '196 181 253', accentSoft: '76 29 149', accentMuted: '91 33 182' },
    },
    rose: {
        label: 'Rose',
        swatch: '#e11d48',
        light: { accent: '225 29 72', accentHover: '244 63 94', accentSoft: '255 241 242', accentMuted: '254 205 211' },
        dark: { accent: '251 113 133', accentHover: '253 164 175', accentSoft: '136 19 55', accentMuted: '159 18 57' },
    },
    amber: {
        label: 'Amber',
        swatch: '#d97706',
        light: { accent: '217 119 6', accentHover: '245 158 11', accentSoft: '255 251 235', accentMuted: '253 230 138' },
        dark: { accent: '251 191 36', accentHover: '252 211 77', accentSoft: '120 53 15', accentMuted: '146 64 14' },
    },
    slate: {
        label: 'Slate',
        swatch: '#475569',
        light: { accent: '71 85 105', accentHover: '100 116 139', accentSoft: '248 250 252', accentMuted: '226 232 240' },
        dark: { accent: '148 163 184', accentHover: '203 213 225', accentSoft: '30 41 59', accentMuted: '51 65 85' },
    },
};

export const sidebarStyles: Record<SidebarStyle, { label: string; description: string }> = {
    light: { label: 'Light', description: 'Clean white sidebar with subtle borders' },
    dark: { label: 'Dark', description: 'High-contrast dark navigation panel' },
    accent: { label: 'Accent', description: 'Tinted with your brand accent color' },
    minimal: { label: 'Minimal', description: 'Soft muted background, borderless feel' },
};

export const topbarStyles: Record<TopbarStyle, { label: string; description: string }> = {
    default: { label: 'Default', description: 'Standard surface with light blur' },
    glass: { label: 'Glass', description: 'Transparent frosted glass effect' },
    solid: { label: 'Solid', description: 'Opaque muted bar for maximum contrast' },
    accent: { label: 'Accent', description: 'Accent bottom border highlight' },
};
