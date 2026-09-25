<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import ColorPicker from '@/Components/ui/ColorPicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    type AccentPreset,
    type ThemePresetId,
    accentPresets,
    getThemePresetsForColorMode,
    sidebarStyles,
    themePresetColorModeLabel,
    themePresets,
    topbarStyles,
    getAccentHexForMode,
    useAppearanceEditor,
} from '@/Composables/useAppearance';
import { confirmAction, confirmDiscard, confirmReset } from '@/Composables/useConfirm';
import { toast } from '@/Composables/useToast';
import type { ThemeMode } from '@/Composables/useTheme';
import type { ColorModeKey, SidebarStyle, TopbarStyle } from '@/Composables/userPreferences';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('appearance');
const { t } = useI18n();
const appearanceLabel = (key: string) => t(`appearanceUi.${key}`);

const {
    draft,
    customizingMode,
    showAdvanced,
    resolvedColorMode,
    customizingAppearance,
    isCustomTheme,
    hasChanges,
    saving,
    saveChanges,
    discardChanges,
    resetDraft,
    setAccentPreset,
    setCustomAccent,
    activeThemePreset,
    applyThemePreset,
    setSidebarStyle,
    setTopbarStyle,
    setCustomSidebarColor,
    clearCustomSidebarColor,
    setCustomTopbarColor,
    clearCustomTopbarColor,
    markCustomized,
} = useAppearanceEditor();

const themeModes: { key: ThemeMode; label: string; hint: string }[] = [
    { key: 'light', label: 'Light', hint: 'Always use light appearance' },
    { key: 'dark', label: 'Dark', hint: 'Always use dark appearance' },
    { key: 'system', label: 'System', hint: 'Match your device setting' },
];

const customizeModes: { key: ColorModeKey; label: string }[] = [
    { key: 'light', label: 'Light appearance' },
    { key: 'dark', label: 'Dark appearance' },
];

const customizingModeLabel = computed(() => (customizingMode.value === 'dark' ? 'Dark' : 'Light'));

const previewAccent = computed(() => getAccentHexForMode(customizingAppearance.value, customizingMode.value));

const activePresetLabel = computed(() =>
    activeThemePreset.value ? themePresets[activeThemePreset.value].label : null,
);

const presetsForColorMode = computed(() => getThemePresetsForColorMode(draft.theme));

const activePresetMatchesColorMode = computed(() =>
    activeThemePreset.value
        ? presetsForColorMode.value.some((preset) => preset.id === activeThemePreset.value)
        : true,
);

const colorModePreferenceLabel = computed(() => themePresetColorModeLabel(draft.theme));

async function handleSave() {
    if (!(await confirmAction({
        title: 'Save appearance settings?',
        message: 'This saves your theme preset, color mode preference, and all light/dark customization.',
        confirmLabel: 'Save changes',
        variant: 'primary',
    }))) {
        return;
    }

    saveChanges(undefined, (message) => toast.error(message));
}

async function handleDiscard() {
    if (!(await confirmDiscard('appearance changes'))) {
        return;
    }

    discardChanges();
    toast.info(t('messages.appearanceDiscarded'));
}

async function handleReset() {
    if (!(await confirmReset('appearance settings to defaults'))) {
        return;
    }

    resetDraft();
    toast.info(t('messages.appearanceReset'));
}
</script>

<template>
    <Head :title="page.header" />

    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="appearance-editor">
        <div class="appearance-editor__header shrink-0 px-4 pt-3 sm:px-5 sm:pt-4 lg:px-6 lg:pt-5">
            <IndexPageHeader
                class="mb-2"
                :back-href="route('dashboard')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            />
        </div>

        <div class="appearance-editor__content">
        <Alert v-if="hasChanges" class="mb-6">
            You have unsaved appearance changes. Click <strong>Save changes</strong> to keep them.
        </Alert>

        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <Card :title="page.title" :description="page.description">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="item in themeModes"
                        :key="item.key"
                        type="button"
                        class="rounded-lg border px-4 py-2 text-left text-sm font-medium transition"
                        :class="
                            draft.theme === item.key
                                ? 'border-accent bg-accent text-white dark:text-zinc-950'
                                : 'border-line bg-surface text-ink-muted hover:bg-surface-muted'
                        "
                        :title="page.title"
                        @click="draft.theme = item.key; markCustomized()"
                    >
                        {{ item.label }}
                    </button>
                </div>
                <p class="mt-3 text-xs text-ink-muted">
                    Currently previewing the
                    <strong>{{ resolvedColorMode === 'dark' ? 'dark' : 'light' }}</strong>
                    appearance based on this preference.
                </p>
            </Card>

            <Card :title="page.title" :description="page.description">
                <div class="overflow-hidden rounded-xl border border-line">
                    <div
                        class="flex h-8 items-center border-b px-3 text-xs font-medium"
                        :style="{
                            backgroundColor: customizingAppearance.customTopbarColor ?? undefined,
                            borderColor: customizingAppearance.topbar === 'accent' ? previewAccent : undefined,
                            borderBottomWidth: customizingAppearance.topbar === 'accent' ? '2px' : undefined,
                        }"
                        :class="{
                            'bg-surface/70 backdrop-blur': customizingAppearance.topbar === 'glass' && !customizingAppearance.customTopbarColor,
                            'bg-surface-muted': customizingAppearance.topbar === 'solid' && !customizingAppearance.customTopbarColor,
                            'bg-surface': (customizingAppearance.topbar === 'default' || customizingAppearance.topbar === 'accent') && !customizingAppearance.customTopbarColor,
                        }"
                    >
                        <span class="text-ink-muted">Top bar</span>
                        <span class="ml-auto rounded px-2 py-0.5 text-[10px] font-medium" :style="{ backgroundColor: previewAccent + '22', color: previewAccent }">
                            {{ customizingModeLabel }}
                        </span>
                    </div>
                    <div class="flex h-36">
                        <div
                            class="w-28 border-r p-2"
                            :style="{
                                backgroundColor: customizingAppearance.customSidebarColor
                                    ?? (customizingAppearance.sidebar === 'accent' ? previewAccent + '18' : undefined),
                            }"
                            :class="{
                                'bg-zinc-900 text-white': customizingAppearance.sidebar === 'dark' && !customizingAppearance.customSidebarColor,
                                'bg-surface-muted': customizingAppearance.sidebar === 'minimal' && !customizingAppearance.customSidebarColor,
                                'bg-surface': customizingAppearance.sidebar === 'light' && !customizingAppearance.customSidebarColor,
                            }"
                        >
                            <div class="mb-2 flex h-6 w-6 items-center justify-center rounded-lg text-[10px] font-bold text-white" :style="{ backgroundColor: previewAccent }">
                                iS
                            </div>
                            <div class="space-y-1">
                                <div class="rounded px-2 py-1 text-[10px] font-medium" :style="{ backgroundColor: previewAccent + '22', color: previewAccent }">
                                    Dashboard
                                </div>
                                <div class="px-2 py-1 text-[10px] opacity-60">Stores</div>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col justify-center gap-3 bg-surface-muted/30 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg px-3 py-1.5 text-xs font-medium text-white" :style="{ backgroundColor: previewAccent }">Primary</span>
                                <Badge variant="neutral">Secondary</Badge>
                            </div>
                            <div class="h-2 w-2/3 overflow-hidden rounded-full bg-surface-muted">
                                <div class="h-full w-1/2 rounded-full transition-all" :style="{ backgroundColor: previewAccent }" />
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <Card
            class="mb-6"
            :title="page.title"
            :description="page.description"
        >
            <Alert
                v-if="activeThemePreset && !activePresetMatchesColorMode"
                variant="info"
                class="mb-4"
            >
                <strong>{{ activePresetLabel }}</strong> is active but belongs to
                {{ themePresetColorModeLabel(themePresets[activeThemePreset].theme).toLowerCase() }} mode.
                Pick a {{ colorModePreferenceLabel.toLowerCase() }} preset below or switch color mode preference.
            </Alert>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="preset in presetsForColorMode"
                    :key="preset.id"
                    type="button"
                    class="group relative overflow-hidden rounded-2xl border p-4 text-left transition duration-200"
                    :class="
                        activeThemePreset === preset.id
                            ? 'border-accent bg-surface shadow-panel ring-2 ring-accent/25'
                            : 'border-line bg-surface hover:border-accent/35 hover:shadow-soft'
                    "
                    @click="applyThemePreset(preset.id as ThemePresetId)"
                >
                    <div
                        class="mb-3 h-16 overflow-hidden rounded-xl shadow-inner ring-1 ring-black/5 dark:ring-white/10"
                        :style="{ background: preset.preview }"
                    />
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">{{ preset.label }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-ink-muted">{{ preset.description }}</p>
                        </div>
                        <span
                            class="mt-0.5 h-4 w-4 shrink-0 rounded-full ring-2 ring-white/80 dark:ring-zinc-900/80"
                            :style="{ backgroundColor: preset.swatch }"
                        />
                    </div>
                    <Badge
                        v-if="activeThemePreset === preset.id"
                        variant="accent"
                        class="absolute right-3 top-3"
                    >
                        Active
                    </Badge>
                </button>
            </div>
        </Card>

        <Card
            class="mb-6"
            :title="page.title"
            :description="page.description"
        >
            <Alert v-if="activePresetLabel && !showAdvanced" variant="info" class="mb-4">
                Using the <strong>{{ activePresetLabel }}</strong> theme. Customizing any option below switches you to a custom theme.
            </Alert>

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="item in customizeModes"
                        :key="item.key"
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            customizingMode === item.key
                                ? 'border-accent bg-accent-soft text-accent'
                                : 'border-line bg-surface text-ink-muted hover:bg-surface-muted'
                        "
                        @click="customizingMode = item.key"
                    >
                        {{ item.label }}
                    </button>
                </div>
                <button
                    v-if="!showAdvanced"
                    type="button"
                    class="text-sm font-medium text-accent hover:underline"
                    @click="showAdvanced = true"
                >
                    Show customization
                </button>
            </div>

            <div v-show="showAdvanced || isCustomTheme" class="grid gap-6 lg:grid-cols-2">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-ink">Brand accent · {{ customizingModeLabel }}</h3>
                    <div class="mb-4 grid grid-cols-4 gap-3">
                        <button
                            v-for="(preset, key) in accentPresets"
                            :key="key"
                            type="button"
                            class="group flex flex-col items-center gap-2 rounded-xl border p-3 transition"
                            :class="
                                customizingAppearance.accentSource === 'preset' && customizingAppearance.accent === key
                                    ? 'border-accent bg-accent-soft shadow-sm ring-2 ring-accent/30'
                                    : 'border-line hover:border-accent/40 hover:bg-surface-muted'
                            "
                            @click="setAccentPreset(key as AccentPreset)"
                        >
                            <span
                                class="h-10 w-10 rounded-full shadow-inner ring-2 ring-white/20 transition group-hover:scale-105"
                                :style="{ backgroundColor: preset.swatch }"
                            />
                            <span class="text-xs font-medium text-ink">{{ preset.label }}</span>
                        </button>
                    </div>
                    <div
                        class="rounded-xl border p-4 transition"
                        :class="customizingAppearance.accentSource === 'custom' ? 'border-accent bg-accent-soft/50 ring-2 ring-accent/20' : 'border-line'"
                    >
                        <ColorPicker
                            :model-value="customizingAppearance.customAccentColor"
                            :label="appearanceLabel('customAccent')"
                            :description="page.description"
                            @update:model-value="setCustomAccent"
                        />
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-ink">Sidebar · {{ customizingModeLabel }}</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="(meta, key) in sidebarStyles"
                                :key="key"
                                type="button"
                                class="rounded-xl border p-3 text-left transition"
                                :class="
                                    customizingAppearance.sidebar === key
                                        ? 'border-accent bg-accent-soft ring-2 ring-accent/20'
                                        : 'border-line hover:border-accent/30 hover:bg-surface-muted'
                                "
                                @click="setSidebarStyle(key as SidebarStyle)"
                            >
                                <div class="text-sm font-semibold text-ink">{{ meta.label }}</div>
                                <div class="mt-0.5 text-xs text-ink-muted">{{ meta.description }}</div>
                            </button>
                        </div>
                        <ColorPicker
                            :model-value="customizingAppearance.customSidebarColor ?? '#ffffff'"
                            :label="appearanceLabel('customSidebarBg')"
                            :description="page.description"
                            @update:model-value="setCustomSidebarColor"
                        />
                        <button
                            v-if="customizingAppearance.customSidebarColor"
                            type="button"
                            class="mt-2 text-xs font-medium text-accent hover:underline"
                            @click="clearCustomSidebarColor"
                        >
                            Clear sidebar color override
                        </button>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-ink">Top bar · {{ customizingModeLabel }}</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="(meta, key) in topbarStyles"
                                :key="key"
                                type="button"
                                class="rounded-xl border p-3 text-left transition"
                                :class="
                                    customizingAppearance.topbar === key
                                        ? 'border-accent bg-accent-soft ring-2 ring-accent/20'
                                        : 'border-line hover:border-accent/30 hover:bg-surface-muted'
                                "
                                @click="setTopbarStyle(key as TopbarStyle)"
                            >
                                <div class="text-sm font-semibold text-ink">{{ meta.label }}</div>
                                <div class="mt-0.5 text-xs text-ink-muted">{{ meta.description }}</div>
                            </button>
                        </div>
                        <ColorPicker
                            :model-value="customizingAppearance.customTopbarColor ?? '#ffffff'"
                            :label="appearanceLabel('customTopbarBg')"
                            :description="page.description"
                            @update:model-value="setCustomTopbarColor"
                        />
                        <button
                            v-if="customizingAppearance.customTopbarColor"
                            type="button"
                            class="mt-2 text-xs font-medium text-accent hover:underline"
                            @click="clearCustomTopbarColor"
                        >
                            Clear top bar color override
                        </button>
                    </div>
                </div>
            </div>
        </Card>

        </div>

        <div
            :aria-label="appearanceLabel('appearanceActions')"
            class="form-action-bar appearance-action-bar shrink-0"
        >
            <p class="min-w-0 text-sm leading-snug text-ink-muted">
                <span v-if="hasChanges" class="font-medium text-amber-600 dark:text-amber-400">
                    Unsaved changes
                    <template v-if="activePresetLabel"> · {{ activePresetLabel }} preset</template>
                </span>
                <span v-else>
                    All changes saved
                    <template v-if="activePresetLabel"> · {{ activePresetLabel }} preset active</template>
                </span>
            </p>
            <div class="form-action-buttons w-full sm:w-auto">
                <Button variant="ghost" @click="handleReset">Reset to defaults</Button>
                <Button variant="secondary" :disabled="!hasChanges || saving" @click="handleDiscard">Discard</Button>
                <Button :disabled="!hasChanges || saving" @click="handleSave">
                    {{ saving ? 'Saving…' : 'Save changes' }}
                </Button>
            </div>
        </div>
        </div>
    </AppLayout>
</template>
