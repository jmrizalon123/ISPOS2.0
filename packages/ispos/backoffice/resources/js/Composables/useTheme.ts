import { resolveThemeMode } from '@/Composables/userPreferences';
import type { ThemeMode } from '@/Composables/useTheme.types';
import { computed, onMounted, ref, watch } from 'vue';

export type { ThemeMode } from '@/Composables/useTheme.types';

function readInitialThemeMode(): ThemeMode {
    const fromDom = document.documentElement.dataset.theme;

    if (fromDom === 'light' || fromDom === 'dark' || fromDom === 'system') {
        return fromDom;
    }

    return 'system';
}

const mode = ref<ThemeMode>(readInitialThemeMode());
const resolved = ref<'light' | 'dark'>(resolveThemeMode(readInitialThemeMode()));
let initialized = false;
let mediaListenerRegistered = false;
let onResolvedChange: ((mode: 'light' | 'dark') => void) | null = null;

export function getResolvedThemeMode(): 'light' | 'dark' {
    return resolved.value;
}

export function registerThemeResolvedListener(listener: (mode: 'light' | 'dark') => void) {
    onResolvedChange = listener;
}

function applyTheme(value: ThemeMode) {
    const nextResolved = resolveThemeMode(value);
    const changed = resolved.value !== nextResolved;

    resolved.value = nextResolved;
    document.documentElement.classList.toggle('dark', nextResolved === 'dark');
    document.documentElement.dataset.theme = value;
    document.documentElement.dataset.colorMode = nextResolved;

    if (changed && onResolvedChange) {
        onResolvedChange(nextResolved);
    }
}

export function syncThemeMode(value: ThemeMode) {
    mode.value = value;
    applyTheme(value);
}

function registerSystemThemeListener() {
    if (mediaListenerRegistered) {
        return;
    }

    mediaListenerRegistered = true;

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', () => {
        if (mode.value === 'system') {
            applyTheme('system');
        }
    });
}

export function useTheme() {
    onMounted(() => {
        registerSystemThemeListener();

        if (!initialized) {
            applyTheme(mode.value);
            initialized = true;
        }
    });

    watch(mode, (value) => applyTheme(value));

    return {
        mode,
        resolved: computed(() => resolved.value),
        setTheme(value: ThemeMode) {
            mode.value = value;
        },
        cycleTheme() {
            const order: ThemeMode[] = ['light', 'dark', 'system'];
            const next = order[(order.indexOf(mode.value) + 1) % order.length];
            mode.value = next;
        },
        syncThemeMode,
    };
}
