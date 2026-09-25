<script setup lang="ts">
import NavIcon from '@/Components/ui/NavIcon.vue';
import { useCommandPalette } from '@/Composables/useCommandPalette';
import { navIconDangerStyle, navIconStyle } from '@/Composables/useNavIconStyle';
import { useNavigation } from '@/Composables/useNavigation';
import { useSidebarNavScroll } from '@/Composables/useSidebarNavScroll';
import { useSidebarSections } from '@/Composables/useSidebarSections';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const page = usePage();

const props = defineProps<{
    collapsed: boolean;
    mobileOpen: boolean;
}>();

const emit = defineEmits<{
    closeMobile: [];
    signOut: [];
}>();

const searchQuery = ref('');
const searchInput = ref<HTMLInputElement | null>(null);
const navRef = ref<HTMLElement | null>(null);
const { openPalette } = useCommandPalette();
const { filteredSections, isActive } = useNavigation(searchQuery);
const { persistScroll, restoreScroll, onNavScroll } = useSidebarNavScroll(navRef);
const { isSectionOpen, ensureInitialSections, openSection, toggleSection } = useSidebarSections(isActive);

const isSearching = computed(() => searchQuery.value.trim().length > 0);

const userDisplayName = computed(() => page.props.auth.user?.name ?? '');
const userAvatarUrl = computed(() => page.props.auth.user?.avatar_url ?? null);

const userInitials = computed(() => {
    const name = userDisplayName.value;
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
});

const displayCompanyName = computed(() => page.props.auth.user?.company?.name ?? null);
const branding = computed(() => page.props.app?.branding);
const brandInitials = computed(() => branding.value?.initials ?? 'iS');
const brandTitle = computed(
    () => `${page.props.app.name} · ${displayCompanyName.value ?? t('common.backoffice')}`,
);

const hasSearchResults = computed(() => filteredSections.value.length > 0);

watch(
    filteredSections,
    (sections) => {
        const sectionItems = Object.fromEntries(
            sections.map((section) => [section.id, section.items.map((item) => item.match)]),
        );

        ensureInitialSections(sectionItems);

        for (const section of sections) {
            if (section.items.some((item) => isActive(item.match))) {
                openSection(section.id);
            }
        }
    },
    { immediate: true },
);

watch(
    () => props.collapsed,
    (collapsed) => {
        if (collapsed) {
            searchQuery.value = '';
        }
    },
);

function focusSearch() {
    if (props.collapsed) {
        openPalette();
        return;
    }

    searchInput.value?.focus();
}

function onNavClick() {
    persistScroll();
    emit('closeMobile');
}

function onSectionToggle(sectionId: string) {
    persistScroll();
    toggleSection(sectionId);
    restoreScroll();
}
</script>

<template>
    <aside
        class="app-sidebar fixed inset-y-0 left-0 z-40 flex h-svh max-h-svh flex-col border-r backdrop-blur-md transition-all duration-200 ease-smooth lg:sticky lg:top-0"
        :class="[
            collapsed ? 'w-[var(--sidebar-collapsed)]' : 'w-[var(--sidebar-width)]',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
    >
        <!-- Brand -->
        <div
            class="app-sidebar-brand flex h-[var(--header-height)] shrink-0 items-center border-b"
            :class="collapsed ? 'justify-center px-2' : 'gap-2.5 px-3'"
            :title="collapsed ? brandTitle : undefined"
        >
            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg ring-1"
                :class="
                    branding?.logo_url
                        ? 'bg-[rgb(var(--sidebar-nav-hover)/0.45)] ring-[rgb(var(--sidebar-border)/0.7)]'
                        : 'app-sidebar-brand-mark bg-accent-soft text-[11px] font-bold text-accent ring-accent/20'
                "
            >
                <img
                    v-if="branding?.logo_url"
                    :src="branding.logo_url"
                    :alt="page.props.app.name"
                    class="h-full w-full object-contain p-1"
                />
                <span v-else>{{ brandInitials }}</span>
            </span>

            <span v-if="!collapsed" class="min-w-0 flex-1">
                <span class="flex min-w-0 items-center gap-1.5">
                    <span
                        class="min-w-0 truncate font-display text-sm font-bold leading-tight tracking-tight text-[rgb(var(--sidebar-text))]"
                    >
                        {{ page.props.app.name }}
                    </span>
                    <span
                        class="app-sidebar-badge shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase leading-none tracking-wide text-[rgb(var(--sidebar-text-muted))]"
                    >
                        {{ t('common.backoffice') }}
                    </span>
                </span>
                <span
                    v-if="displayCompanyName"
                    class="app-sidebar-kicker mt-0.5 block truncate text-[11px] font-medium leading-tight"
                    :title="displayCompanyName"
                >
                    {{ displayCompanyName }}
                </span>
            </span>
        </div>

        <!-- Module search -->
        <div class="shrink-0 px-2.5 pt-2 pb-1">
            <button
                v-if="collapsed"
                type="button"
                class="ui-sidebar-search-btn mx-auto flex h-8 w-8 items-center justify-center rounded-lg border border-[rgb(var(--sidebar-border)/0.6)] bg-[rgb(var(--sidebar-nav-hover)/0.35)] transition hover:bg-[rgb(var(--sidebar-nav-hover)/0.65)]"
                :title="t('common.searchModulesShortcut')"
                @click="focusSearch"
            >
                <NavIcon name="search" />
            </button>
            <div v-else class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[rgb(var(--sidebar-text-muted))]">
                    <NavIcon name="search" />
                </span>
                <input
                    ref="searchInput"
                    v-model="searchQuery"
                    type="search"
                    :placeholder="t('common.searchModules')"
                    class="ui-sidebar-search ui-focus w-full rounded-xl border py-2 pl-9 pr-14 text-[15px] font-medium text-[rgb(var(--sidebar-text))] placeholder:font-normal placeholder:text-[rgb(var(--sidebar-text-muted))] transition"
                />
                <kbd
                    class="pointer-events-none absolute inset-y-0 right-2 my-auto hidden h-6 items-center rounded border border-[rgb(var(--sidebar-border)/0.7)] bg-[rgb(var(--sidebar-bg)/0.8)] px-1.5 font-mono text-[11px] text-[rgb(var(--sidebar-text-muted))] sm:inline-flex"
                >
                    Ctrl K
                </kbd>
            </div>
        </div>

        <!-- Navigation -->
        <nav
            ref="navRef"
            class="sidebar-nav min-h-0 flex-1 overflow-y-auto overscroll-contain px-2.5 py-2"
            @scroll.passive="onNavScroll"
        >
            <template v-if="hasSearchResults">
                <div v-for="section in filteredSections" :key="section.id" class="mb-2 last:mb-0">
                    <button
                        v-if="!collapsed && !isSearching"
                        type="button"
                        class="ui-nav-section-toggle group"
                        :aria-expanded="isSectionOpen(section.id, isSearching)"
                        @click="onSectionToggle(section.id)"
                    >
                        <span class="ui-nav-section-label">{{ section.label }}</span>
                        <span
                            class="ui-nav-section-chevron shrink-0 text-[rgb(var(--sidebar-text-muted)/0.75)] transition-[opacity,transform] duration-200"
                            :class="isSectionOpen(section.id, isSearching) ? 'rotate-180' : ''"
                        >
                            <NavIcon name="chevron-down" />
                        </span>
                    </button>
                    <div
                        v-show="collapsed || isSearching || isSectionOpen(section.id, isSearching)"
                        class="space-y-0.5"
                        :class="!collapsed && !isSearching ? 'mt-0.5' : ''"
                    >
                        <template v-for="item in section.items" :key="item.label">
                            <a
                                v-if="item.opensInNewTab"
                                :href="item.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ui-nav-item group ui-nav-item-idle"
                                :class="collapsed ? 'justify-center px-2' : ''"
                                :title="collapsed ? item.label : undefined"
                                @click="onNavClick"
                            >
                                <span
                                    class="ui-nav-icon ui-nav-icon-tile ui-nav-icon-colored"
                                    :style="navIconStyle(item.match)"
                                >
                                    <NavIcon :name="item.icon" tile />
                                </span>
                                <span v-if="!collapsed" class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                                <span
                                    v-if="!collapsed"
                                    class="app-sidebar-badge shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[rgb(var(--sidebar-text-muted))]"
                                >
                                    App
                                </span>
                            </a>
                            <Link
                                v-else
                                :href="item.href"
                                preserve-scroll
                                class="ui-nav-item group"
                                :class="[
                                    isActive(item.match) ? 'ui-nav-item-active' : 'ui-nav-item-idle',
                                    collapsed ? 'justify-center px-2' : '',
                                ]"
                                :title="collapsed ? item.label : undefined"
                                @click="onNavClick"
                            >
                                <span
                                    class="ui-nav-icon ui-nav-icon-tile ui-nav-icon-colored"
                                    :style="navIconStyle(item.match, isActive(item.match))"
                                >
                                    <NavIcon :name="item.icon" tile />
                                </span>
                                <span v-if="!collapsed" class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </template>

            <div
                v-else-if="!collapsed && isSearching"
                class="rounded-xl border border-dashed border-[rgb(var(--sidebar-border)/0.8)] px-4 py-8 text-center"
            >
                <span class="mx-auto mb-2 flex justify-center opacity-50">
                    <NavIcon name="search" />
                </span>
                <p class="text-sm font-bold text-[rgb(var(--sidebar-text))]">{{ t('common.noModulesFound') }}</p>
                <p class="mt-1 text-xs font-medium text-[rgb(var(--sidebar-text-muted))]">{{ t('common.tryDifferentSearch') }}</p>
            </div>
        </nav>

        <!-- Footer -->
        <div class="app-sidebar-footer mt-auto shrink-0 border-t p-2.5">
            <Link
                v-if="collapsed"
                :href="route('profile.edit')"
                class="ui-sidebar-footer-action ui-nav-item-idle group mb-1 justify-center px-2"
                :title="page.props.auth.user?.name ?? t('common.profile')"
                @click="onNavClick"
            >
                <span class="app-sidebar-avatar">
                    <img v-if="userAvatarUrl" :src="userAvatarUrl" :alt="userDisplayName" class="h-full w-full object-cover" />
                    <template v-else>{{ userInitials }}</template>
                </span>
            </Link>

            <div v-else class="app-sidebar-user mb-1 flex items-center gap-2.5 rounded-xl px-2 py-1.5">
                <span class="app-sidebar-avatar">
                    <img v-if="userAvatarUrl" :src="userAvatarUrl" :alt="userDisplayName" class="h-full w-full object-cover" />
                    <template v-else>{{ userInitials }}</template>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="app-sidebar-user-name truncate text-sm font-semibold leading-tight tracking-tight">
                        {{ page.props.auth.user?.name }}
                    </div>
                    <div class="app-sidebar-kicker mt-0.5 truncate text-[11px] font-medium leading-tight">
                        {{ page.props.auth.user?.email }}
                    </div>
                </div>
            </div>

            <div class="space-y-0.5">
                <Link
                    :href="`${route('profile.edit')}#two-factor`"
                    class="ui-sidebar-footer-action ui-nav-item-idle group"
                    :class="collapsed ? 'justify-center px-2' : ''"
                    :title="collapsed ? t('common.accountSecurity') : undefined"
                    @click="onNavClick"
                >
                    <span
                        class="ui-nav-icon ui-nav-icon-tile ui-nav-icon-colored"
                        :style="navIconStyle('profile.security')"
                    >
                        <NavIcon name="roles" tile />
                    </span>
                    <span v-if="!collapsed" class="min-w-0 flex-1 truncate">{{ t('common.accountSecurity') }}</span>
                </Link>

                <button
                    type="button"
                    class="ui-sidebar-footer-action ui-nav-item-idle group"
                    :class="collapsed ? 'justify-center px-2' : ''"
                    :title="collapsed ? t('common.signOut') : undefined"
                    @click="emit('signOut')"
                >
                    <span class="ui-nav-icon ui-nav-icon-tile ui-nav-icon-colored" :style="navIconDangerStyle()">
                        <NavIcon name="logout" tile />
                    </span>
                    <span v-if="!collapsed" class="min-w-0 flex-1 truncate text-red-500 dark:text-red-400">{{ t('common.signOut') }}</span>
                </button>
            </div>
        </div>
    </aside>
</template>
