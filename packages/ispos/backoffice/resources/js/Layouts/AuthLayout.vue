<script setup lang="ts">
import AppFeedback from '@/Components/feedback/AppFeedback.vue';
import { useAppearance } from '@/Composables/useAppearance';
import { useLocale } from '@/Composables/useLocale';
import { useTheme } from '@/Composables/useTheme';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        welcomeTitle?: string;
        welcomeSubtitle?: string;
        panelClass?: string;
        cardClass?: string;
        /** Lock layout to viewport height; inner card scrolls instead of the page. */
        viewportFit?: boolean;
    }>(),
    {
        panelClass: 'max-w-[420px]',
        cardClass: '',
        viewportFit: false,
    },
);

const { cycleTheme, mode } = useTheme();
const { t } = useLocale();
useAppearance();
const page = usePage();

const branding = computed(() => page.props.app?.branding);
const appName = computed(() => branding.value?.name ?? page.props.app?.name ?? 'iSPOS');
const heading = computed(() => props.welcomeTitle ?? 'Welcome back');
const subheading = computed(() => props.welcomeSubtitle ?? `Sign in to continue to ${appName.value}`);
const brandInitials = computed(() => branding.value?.initials ?? 'iS');
const brandTagline = computed(() => branding.value?.tagline || t('auth.brandTagline'));
const brandDescription = computed(() => t('auth.brandDescription'));
const brandFeatures = computed(() => [
    t('auth.brandFeatureMultiStore'),
    t('auth.brandFeatureRoles'),
    t('auth.brandFeatureAudit'),
]);
const brandCopyright = computed(() => branding.value?.copyright ?? `© ${new Date().getFullYear()} iSPOS`);
</script>

<template>
    <div
        class="relative flex"
        :class="viewportFit ? 'h-svh overflow-hidden' : 'min-h-screen'"
    >
        <!-- Brand panel -->
        <div
            class="relative hidden w-[44%] flex-col justify-between overflow-hidden bg-zinc-950 p-10 text-white lg:flex xl:w-[42%]"
        >
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgb(13_148_136/0.35),transparent_55%),radial-gradient(ellipse_at_bottom_right,rgb(15_118_110/0.2),transparent_50%)]" />
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImEiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VybG9jZU9uU3BhY2UiPjxwYXRoIGQ9Ik0wIDYwaDYwVjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiKDI1NSAyNTUgMjU1IC8gMC4wMykiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IGZpbGw9InVybCgjYSkiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiLz48L3N2Zz4=')] opacity-40" />

            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white/10 font-display text-lg font-bold backdrop-blur"
                    >
                        <img
                            v-if="branding?.logo_url"
                            :src="branding.logo_url"
                            :alt="appName"
                            class="h-full w-full object-contain p-1.5"
                        />
                        <span v-else>{{ brandInitials }}</span>
                    </div>
                    <span class="font-display text-xl font-bold tracking-tight">{{ appName }}</span>
                </div>
            </div>

            <div class="relative z-10 max-w-md space-y-6">
                <h2 class="font-display text-3xl font-bold leading-tight tracking-tight xl:text-4xl">
                    {{ brandTagline }}
                </h2>
                <p class="text-sm leading-relaxed text-zinc-400">
                    {{ brandDescription }}
                </p>
                <div class="flex flex-wrap gap-3 text-xs text-zinc-500">
                    <span
                        v-for="feature in brandFeatures"
                        :key="feature"
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1"
                    >
                        {{ feature }}
                    </span>
                </div>
            </div>

            <p class="relative z-10 text-xs text-zinc-600">{{ brandCopyright }}</p>
        </div>

        <!-- Form panel -->
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div class="flex shrink-0 justify-end gap-2 p-4 sm:p-6">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('appearance.edit')"
                    class="rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink-muted shadow-sm transition hover:bg-surface-muted"
                >
                    Appearance
                </Link>
                <button
                    type="button"
                    class="rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-medium capitalize text-ink-muted shadow-sm transition hover:bg-surface-muted"
                    @click="cycleTheme"
                >
                    {{ mode }}
                </button>
            </div>

            <div
                class="flex min-h-0 flex-1 flex-col px-4 sm:px-8"
                :class="
                    viewportFit
                        ? 'overflow-hidden pb-4 pt-2 sm:pb-6'
                        : 'scrollbar-visible items-start justify-center overflow-y-auto py-6 lg:items-center lg:py-10'
                "
            >
                <div
                    class="w-full animate-fade-in"
                    :class="[
                        panelClass,
                        viewportFit ? 'flex min-h-0 flex-1 flex-col' : 'my-auto',
                    ]"
                >
                    <div class="shrink-0 lg:hidden" :class="viewportFit ? 'mb-4' : 'mb-8'">
                        <div
                            class="mb-3 flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-accent to-teal-600 font-display text-lg font-bold text-white shadow-sm"
                            :class="viewportFit ? 'mb-2 h-10 w-10 text-base' : 'mb-4'"
                        >
                            <img
                                v-if="branding?.logo_url"
                                :src="branding.logo_url"
                                :alt="appName"
                                class="h-full w-full object-contain p-2"
                            />
                            <span v-else>{{ brandInitials }}</span>
                        </div>
                        <h1
                            class="font-display font-bold tracking-tight text-ink"
                            :class="viewportFit ? 'text-xl' : 'text-2xl'"
                        >
                            {{ heading }}
                        </h1>
                        <p class="mt-1 text-sm text-ink-muted">{{ subheading }}</p>
                    </div>

                    <div class="hidden shrink-0 lg:block">
                        <h1
                            class="font-display font-bold tracking-tight text-ink"
                            :class="viewportFit ? 'text-xl xl:text-2xl' : 'text-2xl'"
                        >
                            {{ heading }}
                        </h1>
                        <p class="mt-1.5 text-sm text-ink-muted">{{ subheading }}</p>
                    </div>

                    <div
                        class="rounded-2xl border border-line bg-surface shadow-panel"
                        :class="[
                            cardClass,
                            viewportFit
                                ? 'mt-4 flex min-h-0 flex-1 flex-col overflow-hidden lg:mt-5'
                                : 'mt-8 p-6 sm:p-8',
                        ]"
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </div>

        <AppFeedback />
    </div>
</template>
