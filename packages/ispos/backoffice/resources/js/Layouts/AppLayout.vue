<script setup lang="ts">
import AppFooter from '@/Components/layout/AppFooter.vue';
import LocaleSwitcher from '@/Components/layout/LocaleSwitcher.vue';
import Sidebar from '@/Components/layout/Sidebar.vue';
import ContextSwitcher from '@/Components/layout/ContextSwitcher.vue';
import StockAlertBell from '@/Components/layout/StockAlertBell.vue';
import UserMenuDropdown from '@/Components/layout/UserMenuDropdown.vue';
import GoogleTranslateWidget from '@/Components/translation/GoogleTranslateWidget.vue';
import { useTranslation } from '@/Composables/useTranslation';
import NavIcon from '@/Components/ui/NavIcon.vue';
import CommandPalette from '@/Components/ui/CommandPalette.vue';
import AppFeedback from '@/Components/feedback/AppFeedback.vue';
import { confirmAction } from '@/Composables/useConfirm';
import { useAppearance, useQuickThemeToggle } from '@/Composables/useAppearance';
import { Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

useAppearance();
const { t } = useTranslation();

onMounted(() => document.documentElement.classList.add('app-shell'));
onUnmounted(() => document.documentElement.classList.remove('app-shell'));
const { mode, cycleThemeFromTopbar } = useQuickThemeToggle();

const collapsed = ref(localStorage.getItem('ispos-sidebar') === '1');
const mobileOpen = ref(false);

function toggleSidebar() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('ispos-sidebar', collapsed.value ? '1' : '0');
}

function onSidebarToggle() {
    if (window.matchMedia('(max-width: 1023px)').matches) {
        mobileOpen.value = !mobileOpen.value;
        return;
    }

    toggleSidebar();
}

async function signOut() {
    const confirmed = await confirmAction({
        title: `${t('common.signOut')}?`,
        message: t('common.signOutConfirm'),
        confirmLabel: t('common.signOut'),
        variant: 'warning',
    });

    if (confirmed) {
        router.post(route('logout'));
    }
}
</script>

<template>
    <div class="flex h-svh overflow-hidden">
        <Sidebar
            :collapsed="collapsed"
            :mobile-open="mobileOpen"
            @close-mobile="mobileOpen = false"
            @sign-out="signOut"
        />

        <div v-if="mobileOpen" class="fixed inset-0 z-30 bg-zinc-950/50 backdrop-blur-sm lg:hidden" @click="mobileOpen = false" />

        <!-- Main column -->
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <header
                class="app-topbar sticky top-0 z-20 flex h-[var(--header-height)] shrink-0 items-center justify-between gap-4 border-b pl-0.5 pr-4 sm:pl-1 sm:pr-6"
            >
                <div class="flex min-w-0 items-center gap-1.5">
                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-surface-muted hover:text-ink [&_svg]:h-[22px] [&_svg]:w-[22px]"
                        :title="collapsed ? t('common.expandSidebar') : t('common.collapseSidebar')"
                        @click="onSidebarToggle"
                    >
                        <NavIcon name="menu" />
                    </button>

                    <div class="min-w-0">
                        <div v-if="$slots.header" class="flex min-w-0 items-center gap-1.5">
                            <slot name="headerBack" />
                            <h1 class="truncate font-display text-base font-bold tracking-tight text-ink">
                                <slot name="header" />
                            </h1>
                            <span v-if="$slots.subheader" class="hidden truncate text-xs font-medium text-ink-muted sm:inline">
                                <span class="text-ink-muted/40">·</span>
                                <slot name="subheader" />
                            </span>
                        </div>
                        <p v-if="!$slots.header && !$slots.subheader" class="hidden text-xs text-ink-muted sm:block">
                            {{ t('common.searchHint', { ctrl: 'Ctrl' }) }}
                        </p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <ContextSwitcher />
                    <div class="topbar-tools">
                        <div class="topbar-icon-group" role="group" aria-label="Quick actions">
                            <StockAlertBell />
                            <LocaleSwitcher class="hidden sm:block" />
                            <Link
                                :href="route('appearance.edit')"
                                class="topbar-icon-btn topbar-icon-btn--appearance hidden sm:inline-flex"
                                :class="{ 'topbar-icon-btn--active': route().current('appearance.edit') }"
                                :title="t('common.appearance')"
                                :aria-label="t('common.appearance')"
                            >
                                <NavIcon name="appearance" subtle />
                            </Link>
                            <button
                                type="button"
                                class="topbar-icon-btn topbar-icon-btn--theme relative z-30 hidden sm:inline-flex"
                                :class="`topbar-icon-btn--theme-${mode}`"
                                :title="`Color mode: ${mode} (click to cycle)`"
                                :aria-label="`Color mode: ${mode}`"
                                @click="cycleThemeFromTopbar"
                            >
                                <NavIcon :name="mode === 'dark' ? 'moon' : mode === 'system' ? 'computer' : 'sun'" subtle />
                            </button>
                        </div>
                        <span class="topbar-tools__rule" aria-hidden="true" />
                        <UserMenuDropdown />
                    </div>
                </div>
            </header>

            <main class="scrollbar-visible min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-3 sm:px-5 sm:py-4 lg:px-6 lg:py-5">
                <div class="app-page animate-fade-in">
                    <slot />
                </div>
            </main>

            <AppFooter />
        </div>

        <CommandPalette />
        <AppFeedback />
        <GoogleTranslateWidget />
    </div>
</template>
