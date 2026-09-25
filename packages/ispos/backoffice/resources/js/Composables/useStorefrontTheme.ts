import { computed, onMounted, ref, watch } from 'vue';

export type StorefrontThemeMode = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'ispos.storefront.theme';

function readStoredMode(): StorefrontThemeMode {
    if (typeof window === 'undefined') return 'system';
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        if (value === 'light' || value === 'dark' || value === 'system') {
            return value;
        }
    } catch {
        // ignore storage errors
    }
    return 'system';
}

function systemPrefersDark(): boolean {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function resolveMode(mode: StorefrontThemeMode): 'light' | 'dark' {
    if (mode === 'system') {
        return systemPrefersDark() ? 'dark' : 'light';
    }
    return mode;
}

const mode = ref<StorefrontThemeMode>(readStoredMode());
const resolved = ref<'light' | 'dark'>(resolveMode(mode.value));
let mediaListenerRegistered = false;

function persist(value: StorefrontThemeMode): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // ignore storage errors
    }
}

function applyResolved(): void {
    resolved.value = resolveMode(mode.value);
}

function registerSystemListener(): void {
    if (mediaListenerRegistered || typeof window === 'undefined') return;
    mediaListenerRegistered = true;
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (mode.value === 'system') {
            applyResolved();
        }
    });
}

/** Light / dark / system theme for public storefront pages (localStorage). */
export function useStorefrontTheme() {
    onMounted(() => {
        registerSystemListener();
        applyResolved();
    });

    watch(mode, (value) => {
        persist(value);
        applyResolved();
    });

    const colorModeAttrs = computed(() => ({
        'data-theme': mode.value,
        'data-color-mode': resolved.value,
    }));

    function setTheme(value: StorefrontThemeMode): void {
        mode.value = value;
    }

    function cycleTheme(): void {
        const order: StorefrontThemeMode[] = ['light', 'dark', 'system'];
        mode.value = order[(order.indexOf(mode.value) + 1) % order.length];
    }

    const themeLabel = computed(() => {
        if (mode.value === 'system') return `System (${resolved.value})`;
        return mode.value === 'dark' ? 'Dark' : 'Light';
    });

    return {
        mode,
        resolved: computed(() => resolved.value),
        themeLabel,
        colorModeAttrs,
        setTheme,
        cycleTheme,
    };
}
