import { type AccentPreset } from '@/Composables/appearancePresets';
import { getAccentTokensForMode, hexToRgb, rgbString } from '@/Composables/colorUtils';
import {
    applyThemePresetToPreferences,
    detectThemePreset,
    syncThemePreset,
    type ThemePresetId,
} from '@/Composables/themePresets';
import {
    appearanceDefaults,
    cloneUserPreferences,
    defaultModeAppearanceMap,
    defaultUserPreferences,
    getAppearanceForColorMode,
    normalizeUserPreferences,
    readSavedUserPreferences,
    resolveThemeMode,
    userPreferencesEqual,
    type AccentSource,
    type AppearanceState,
    type ColorModeKey,
    type SidebarStyle,
    type TopbarStyle,
    type UserPreferences,
} from '@/Composables/userPreferences';
import { getResolvedThemeMode, registerThemeResolvedListener, syncThemeMode, useTheme, type ThemeMode } from '@/Composables/useTheme';
import { translate } from '@/plugins/i18n';
import type { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

export type { AccentPreset, AccentTokens } from '@/Composables/appearancePresets';
export { accentPresets, sidebarStyles, topbarStyles } from '@/Composables/appearancePresets';
export {
    getThemePresetsForColorMode,
    themePresetColorModeLabel,
    themePresets,
    themePresetsByColorMode,
    themePresetList,
    type ThemePresetId,
} from '@/Composables/themePresets';

export type { AccentSource, AppearanceState, ColorModeKey, SidebarStyle, TopbarStyle } from '@/Composables/userPreferences';
export { appearanceDefaults, getEditingModeKey } from '@/Composables/userPreferences';

function applyAccentTokens(tokens: { accent: string; accentHover: string; accentSoft: string; accentMuted: string }) {
    const root = document.documentElement;
    root.style.setProperty('--color-accent', tokens.accent);
    root.style.setProperty('--color-accent-hover', tokens.accentHover);
    root.style.setProperty('--color-accent-soft', tokens.accentSoft);
    root.style.setProperty('--color-accent-muted', tokens.accentMuted);
}

function applyCustomSurfaceVars(state: AppearanceState) {
    const root = document.documentElement;

    if (state.customSidebarColor) {
        const rgb = hexToRgb(state.customSidebarColor);
        if (rgb) {
            root.style.setProperty('--sidebar-bg', rgbString(rgb));
            root.dataset.sidebarCustom = '1';
        }
    } else {
        root.style.removeProperty('--sidebar-bg');
        delete root.dataset.sidebarCustom;
    }

    if (state.customTopbarColor) {
        const rgb = hexToRgb(state.customTopbarColor);
        if (rgb) {
            root.style.setProperty('--topbar-bg', rgbString(rgb));
            root.dataset.topbarCustom = '1';
        }
    } else {
        root.style.removeProperty('--topbar-bg');
        delete root.dataset.topbarCustom;
    }
}

export function applyAppearance(state: AppearanceState, resolvedMode: ColorModeKey = 'light') {
    const root = document.documentElement;
    const tokens = getAccentTokensForMode(state, resolvedMode);

    if (tokens) {
        applyAccentTokens(tokens);
    }

    root.dataset.accent = state.accentSource === 'custom' ? 'custom' : state.accent;
    root.dataset.sidebar = state.sidebar;
    root.dataset.topbar = state.topbar;
    root.dataset.colorMode = resolvedMode;
    applyCustomSurfaceVars(state);
}

function applyUserPreferences(preferences: UserPreferences) {
    syncThemeMode(preferences.theme);
    const colorMode = getResolvedThemeMode();
    applyAppearance(getAppearanceForColorMode(preferences, colorMode), colorMode);
}

export { getAccentHexForMode, getAccentTokensForMode } from '@/Composables/colorUtils';

const applied = ref<UserPreferences>(normalizeUserPreferences(null));
const livePreferences = ref<UserPreferences | null>(null);
let initialized = false;
let themeWatchRegistered = false;

function syncAppliedFromPage() {
    applied.value = readSavedUserPreferences();
}

function getActivePreferences(): UserPreferences {
    return livePreferences.value ?? applied.value;
}

function applySaved() {
    applyUserPreferences(applied.value);
}

function registerThemeWatch() {
    if (themeWatchRegistered) {
        return;
    }
    themeWatchRegistered = true;

    registerThemeResolvedListener((mode) => {
        if (initialized) {
            applyAppearance(getAppearanceForColorMode(getActivePreferences(), mode), mode);
        }
    });
}

function registerPreferencesWatch() {
    const page = usePage<PageProps>();

    watch(
        () => page.props.auth.user?.preferences,
        (preferences) => {
            if (!preferences) {
                return;
            }

            applied.value = normalizeUserPreferences(preferences);
            if (initialized) {
                applySaved();
            }
        },
        { deep: true },
    );
}

function serializeAppearancePayload(preferences: UserPreferences) {
    const synced = syncThemePreset(preferences);

    return {
        themePreset: synced.themePreset ?? null,
        light: { ...synced.appearance.light },
        dark: { ...synced.appearance.dark },
    };
}

function persistPreferences(preferences: UserPreferences, options?: { preserveScroll?: boolean; preserveState?: boolean }) {
    const page = usePage<PageProps>();

    if (!page.props.auth.user) {
        return;
    }

    router.put(
        route('appearance.update'),
        {
            theme: preferences.theme,
            appearance: serializeAppearancePayload(preferences),
        },
        {
            preserveScroll: options?.preserveScroll ?? true,
            preserveState: options?.preserveState ?? true,
        },
    );
}

/** Cycle light → dark → system from the top bar and persist for signed-in users. */
export function useQuickThemeToggle() {
    const { mode } = useTheme();
    const page = usePage<PageProps>();

    function cycleThemeFromTopbar() {
        const order: ThemeMode[] = ['light', 'dark', 'system'];
        const current = getActivePreferences().theme;
        const next = order[(order.indexOf(current) + 1) % order.length];
        const prefs = syncThemePreset({
            ...cloneUserPreferences(getActivePreferences()),
            theme: next,
        });

        applied.value = prefs;
        applyUserPreferences(prefs);

        if (!page.props.auth.user) {
            return;
        }

        persistPreferences(prefs);
    }

    return { mode, cycleThemeFromTopbar };
}

/** Apply saved appearance on app shell mount (no draft changes). */
export function useAppearance() {
    registerThemeWatch();
    registerPreferencesWatch();

    onMounted(() => {
        if (initialized) {
            return;
        }

        syncAppliedFromPage();
        initialized = true;
        applySaved();
    });
}

/** Draft editor for the appearance settings page — live preview on the shell until saved. */
export function useAppearanceEditor() {
    registerThemeWatch();

    const draft = reactive<UserPreferences>(cloneUserPreferences(applied.value));
    const saving = ref(false);
    const customizingMode = ref<ColorModeKey>(resolveThemeMode(draft.theme));
    const showAdvanced = ref(!draft.themePreset);

    const resolvedColorMode = computed(() => resolveThemeMode(draft.theme));
    const draftResolvedMode = resolvedColorMode;
    const customizingAppearance = computed(() => draft.appearance[customizingMode.value]);
    const isCustomTheme = computed(() => activeThemePreset.value === null);

    function applyDraftPreview() {
        const prefs = cloneUserPreferences(draft);
        livePreferences.value = prefs;
        applyUserPreferences(prefs);
    }

    function syncPresetFromDraft() {
        draft.themePreset = detectThemePreset(draft);
    }

    function markCustomized() {
        syncPresetFromDraft();
    }

    watch(draft, applyDraftPreview, { deep: true });
    watch(() => draft.theme, applyDraftPreview);

    watch(
        () => [draft.appearance.light, draft.appearance.dark, draft.theme] as const,
        syncPresetFromDraft,
        { deep: true },
    );

    onMounted(() => {
        syncAppliedFromPage();
        Object.assign(draft, cloneUserPreferences(applied.value));
        customizingMode.value = resolvedColorMode.value;
        showAdvanced.value = !draft.themePreset;
        applyDraftPreview();
    });

    onBeforeUnmount(() => {
        livePreferences.value = null;
        applySaved();
    });

    const hasChanges = computed(() => !userPreferencesEqual(draft, applied.value));

    const activeThemePreset = computed(() => detectThemePreset(draft));

    function applyThemePreset(presetId: ThemePresetId) {
        const next = applyThemePresetToPreferences(cloneUserPreferences(draft), presetId);
        draft.theme = next.theme;
        draft.themePreset = next.themePreset ?? null;
        draft.appearance.light = { ...next.appearance.light };
        draft.appearance.dark = { ...next.appearance.dark };
        customizingMode.value = resolveThemeMode(next.theme);
        showAdvanced.value = false;
    }

    function saveChanges(onSuccess?: () => void, onError?: (message: string) => void) {
        if (saving.value) {
            return;
        }

        saving.value = true;

        const page = usePage<PageProps>();
        const csrfToken = page.props.app?.csrf_token;
        const headers = csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : undefined;
        const payload = syncThemePreset(cloneUserPreferences(draft));

        router.put(
            route('appearance.update'),
            {
                theme: payload.theme,
                appearance: serializeAppearancePayload(payload),
            },
            {
                preserveScroll: true,
                headers,
                onSuccess: () => {
                    applied.value = cloneUserPreferences(payload);
                    livePreferences.value = cloneUserPreferences(payload);
                    Object.assign(draft, cloneUserPreferences(payload));
                    onSuccess?.();
                },
                    onError: () => {
                    onError?.(translate('messages.appearanceSaveError'));
                },
                onFinish: () => {
                    saving.value = false;
                },
            },
        );
    }

    function discardChanges() {
        Object.assign(draft, cloneUserPreferences(applied.value));
        customizingMode.value = resolveThemeMode(draft.theme);
        showAdvanced.value = !draft.themePreset;
    }

    function resetDraft() {
        const defaults = defaultUserPreferences();
        draft.theme = defaults.theme;
        draft.themePreset = defaults.themePreset ?? null;
        draft.appearance.light = { ...defaults.appearance.light };
        draft.appearance.dark = { ...defaults.appearance.dark };
        customizingMode.value = resolvedColorMode.value;
        showAdvanced.value = true;
    }

    function setAccentPreset(value: AccentPreset) {
        const state = draft.appearance[customizingMode.value];
        state.accentSource = 'preset';
        state.accent = value;
        markCustomized();
    }

    function setCustomAccent(value: string) {
        const state = draft.appearance[customizingMode.value];
        state.accentSource = 'custom';
        state.customAccentColor = value;
        markCustomized();
    }

    function setSidebarStyle(value: SidebarStyle) {
        draft.appearance[customizingMode.value].sidebar = value;
        markCustomized();
    }

    function setTopbarStyle(value: TopbarStyle) {
        draft.appearance[customizingMode.value].topbar = value;
        markCustomized();
    }

    function setCustomSidebarColor(value: string) {
        draft.appearance[customizingMode.value].customSidebarColor = value;
        markCustomized();
    }

    function clearCustomSidebarColor() {
        draft.appearance[customizingMode.value].customSidebarColor = null;
        markCustomized();
    }

    function setCustomTopbarColor(value: string) {
        draft.appearance[customizingMode.value].customTopbarColor = value;
        markCustomized();
    }

    function clearCustomTopbarColor() {
        draft.appearance[customizingMode.value].customTopbarColor = null;
        markCustomized();
    }

    return {
        draft,
        customizingMode,
        showAdvanced,
        resolvedColorMode,
        customizingAppearance,
        isCustomTheme,
        hasChanges,
        saving,
        draftResolvedMode,
        activeThemePreset,
        applyThemePreset,
        saveChanges,
        discardChanges,
        resetDraft,
        setAccentPreset,
        setCustomAccent,
        setSidebarStyle,
        setTopbarStyle,
        setCustomSidebarColor,
        clearCustomSidebarColor,
        setCustomTopbarColor,
        clearCustomTopbarColor,
        markCustomized,
    };
}
