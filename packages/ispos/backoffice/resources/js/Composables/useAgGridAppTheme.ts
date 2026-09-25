import { registerThemeResolvedListener } from '@/Composables/useTheme';
import { useTheme } from '@/Composables/useTheme';
import { themeQuartz } from 'ag-grid-community';
import { computed, ref } from 'vue';

export function readCssRgb(name: string, fallback: string): string {
    if (typeof document === 'undefined') {
        return fallback;
    }

    const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return raw ? `rgb(${raw})` : fallback;
}

let themeListenerRegistered = false;

export function useAgGridAppTheme() {
    const { resolved } = useTheme();
    const themeEpoch = ref(0);

    if (!themeListenerRegistered && typeof window !== 'undefined') {
        registerThemeResolvedListener(() => {
            themeEpoch.value += 1;
        });
        themeListenerRegistered = true;
    }

    const gridTheme = computed(() => {
        void themeEpoch.value;
        const isDark = resolved.value === 'dark';

        const accent = readCssRgb('--color-accent', isDark ? '#60a5fa' : '#2563eb');
        const surface = readCssRgb('--color-surface', isDark ? '#18181b' : '#ffffff');
        const surfaceMuted = readCssRgb('--color-surface-muted', isDark ? '#27272a' : '#f4f4f5');
        const border = readCssRgb('--color-border', isDark ? '#3f3f46' : '#e4e4e7');
        const text = readCssRgb('--color-text', isDark ? '#fafafa' : '#18181b');
        const textMuted = readCssRgb('--color-text-muted', isDark ? '#a1a1aa' : '#71717a');
        const accentSoft = readCssRgb('--color-accent-soft', isDark ? '#1e3a8a' : '#eff6ff');

        return themeQuartz.withParams({
            accentColor: accent,
            backgroundColor: surface,
            foregroundColor: text,
            borderColor: border,
            chromeBackgroundColor: surfaceMuted,
            headerBackgroundColor: surfaceMuted,
            headerTextColor: textMuted,
            cellTextColor: text,
            oddRowBackgroundColor: surface,
            rowHoverColor: surfaceMuted,
            selectedRowBackgroundColor: isDark
                ? `color-mix(in srgb, ${accent} 20%, ${surface})`
                : `color-mix(in srgb, ${accentSoft} 70%, ${surface})`,
            browserColorScheme: isDark ? 'dark' : 'light',
            fontSize: 13,
            headerHeight: 36,
            rowHeight: 40,
            paginationPanelHeight: 42,
            spacing: 6,
            wrapperBorder: false,
            wrapperBorderRadius: 0,
        });
    });

    return { gridTheme, readCssRgb };
}
