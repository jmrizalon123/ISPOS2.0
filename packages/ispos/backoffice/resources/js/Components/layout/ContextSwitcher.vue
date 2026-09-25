<script setup lang="ts">
import Dropdown from '@/Components/ui/Dropdown.vue';
import Input from '@/Components/ui/Input.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import { useLocale } from '@/Composables/useLocale';
import type { BackofficeContext, PageProps } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { t } = useLocale();
const page = usePage<PageProps>();

const context = computed(() => page.props.auth.context as BackofficeContext | null | undefined);
const canSwitch = computed(() => Boolean(page.props.auth.canSwitchContext));
const options = computed(() => page.props.auth.contextOptions);

const dropdownOpen = ref(false);
const panelTab = ref<'company' | 'stores'>('stores');
const searchQuery = ref('');

const isCompanyScope = computed(() => context.value?.scope === 'company');

const triggerTitle = computed(() => {
    if (!context.value) {
        return '';
    }

    if (isCompanyScope.value) {
        return context.value.label;
    }

    return context.value.label;
});

const triggerMeta = computed(() => {
    if (!context.value) {
        return '';
    }

    if (isCompanyScope.value) {
        return t('auth.contextCompanyShort');
    }

    return context.value.store_code || t('common.store');
});

const companyLabel = computed(() => {
    const company = options.value?.company;
    if (!company) {
        return t('auth.contextCompany');
    }

    return company.display_name || company.name;
});

const companyCode = computed(() => options.value?.company?.company_code ?? null);

const storeCount = computed(() => options.value?.stores.length ?? 0);

const showPanelTabs = computed(
    () => Boolean(options.value?.can_select_company) && storeCount.value > 0,
);

const showStoreSearch = computed(() => storeCount.value >= 3 && panelTab.value === 'stores');

const filteredStores = computed(() => {
    const stores = options.value?.stores ?? [];
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return stores;
    }

    return stores.filter(
        (store) =>
            store.store_name.toLowerCase().includes(query)
            || store.store_code.toLowerCase().includes(query),
    );
});

const form = useForm({
    scope: 'store' as 'company' | 'store',
    store_id: '',
});

watch(dropdownOpen, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
        return;
    }

    panelTab.value = isCompanyScope.value ? 'company' : 'stores';
});

function isActiveCompany() {
    return context.value?.scope === 'company';
}

function isActiveStore(storeId: string) {
    return context.value?.scope === 'store' && context.value.store_id === storeId;
}

function switchToCompany() {
    if (isActiveCompany() || form.processing) {
        return;
    }

    form.scope = 'company';
    form.store_id = '';
    form.post(route('login-context.store'), {
        preserveScroll: true,
        onSuccess: () => {
            dropdownOpen.value = false;
        },
    });
}

function switchToStore(storeId: string) {
    if (isActiveStore(storeId) || form.processing) {
        return;
    }

    form.scope = 'store';
    form.store_id = storeId;
    form.post(route('login-context.store'), {
        preserveScroll: true,
        onSuccess: () => {
            dropdownOpen.value = false;
        },
    });
}
</script>

<template>
    <div v-if="context" class="flex min-w-0 items-center">
        <Dropdown
            v-if="canSwitch && options"
            v-model:open="dropdownOpen"
            align="right"
            width="80"
            mobile-mode="sheet"
            :close-on-content-click="false"
            content-classes="min-h-0 overflow-hidden bg-surface p-0 shadow-panel"
        >
            <template #trigger="{ open }">
                <button
                    type="button"
                    class="group flex h-9 max-w-[10.5rem] items-center gap-2 rounded-lg border pl-1 pr-1.5 text-left shadow-sm transition duration-150 sm:max-w-[15rem] sm:pr-2 lg:max-w-sm"
                    :class="
                        open
                            ? 'border-accent/40 bg-accent-soft/30 ring-2 ring-accent/15'
                            : 'border-line bg-surface hover:border-accent/25 hover:bg-surface-muted/60'
                    "
                    :title="`${triggerTitle} · ${triggerMeta}`"
                    :disabled="form.processing"
                    :aria-expanded="open"
                >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md transition [&_svg]:h-4 [&_svg]:w-4"
                        :class="
                            isCompanyScope
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300'
                                : 'bg-accent-soft text-accent'
                        "
                    >
                        <NavIcon :name="isCompanyScope ? 'companies' : 'stores'" />
                    </span>
                    <span class="min-w-0 truncate text-[13px] font-semibold leading-none text-ink">
                        {{ triggerTitle }}
                    </span>
                    <span
                        class="hidden shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase leading-none tracking-wider sm:inline-block"
                        :class="
                            isCompanyScope
                                ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                : 'bg-surface-muted font-mono text-ink-muted'
                        "
                    >
                        {{ triggerMeta }}
                    </span>
                    <span
                        class="shrink-0 transition duration-200 [&_svg]:h-3.5 [&_svg]:w-3.5"
                        :class="open ? 'rotate-180 text-accent' : 'text-ink-muted group-hover:text-ink'"
                    >
                        <NavIcon name="chevron-down" />
                    </span>
                </button>
            </template>

            <template #content="{ sheet }">
                <div v-if="sheet" class="flex shrink-0 justify-center pt-2.5" aria-hidden="true">
                    <span class="h-1 w-10 rounded-full bg-line/90" />
                </div>

                <div class="shrink-0 border-b border-line/70 px-4 py-3.5 sm:px-5" @click.stop>
                    <p class="font-display text-sm font-bold text-ink sm:text-base">{{ t('auth.switchContext') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-ink-muted">{{ t('auth.selectContextDescription') }}</p>
                </div>

                <div v-if="showPanelTabs" class="border-b border-line/70 px-4 py-3 sm:px-5" @click.stop>
                    <div
                        class="inline-flex w-full gap-1 rounded-xl border border-line/80 bg-surface-muted/50 p-1"
                        role="tablist"
                    >
                        <button
                            type="button"
                            role="tab"
                            class="min-w-0 flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition"
                            :class="
                                panelTab === 'company'
                                    ? 'bg-surface text-ink shadow-sm ring-1 ring-line/60'
                                    : 'text-ink-muted hover:text-ink'
                            "
                            :aria-selected="panelTab === 'company'"
                            @click="panelTab = 'company'"
                        >
                            {{ t('auth.contextCompanyShort') }}
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="min-w-0 flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition"
                            :class="
                                panelTab === 'stores'
                                    ? 'bg-surface text-ink shadow-sm ring-1 ring-line/60'
                                    : 'text-ink-muted hover:text-ink'
                            "
                            :aria-selected="panelTab === 'stores'"
                            @click="panelTab = 'stores'"
                        >
                            {{ t('auth.contextStoresTab') }}
                            <span class="ml-1 text-[11px] font-medium text-ink-muted">({{ storeCount }})</span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="showStoreSearch"
                    class="border-b border-line/70 px-4 py-3 sm:px-5"
                    @click.stop
                >
                    <div class="relative [&_input]:!h-9 [&_input]:!py-2 [&_input]:!pl-9 [&_input]:!text-sm">
                        <NavIcon
                            name="search"
                            class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-ink-muted"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            :placeholder="t('auth.searchStore')"
                            autocomplete="off"
                        />
                    </div>
                </div>

                <div
                    class="scrollbar-visible min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:max-h-[min(18rem,52vh)] sm:flex-none sm:px-4"
                    @click.stop
                >
                    <div
                        v-if="(!showPanelTabs || panelTab === 'company') && options.can_select_company"
                        class="space-y-2"
                    >
                        <button
                            type="button"
                            class="group flex w-full items-start gap-3 rounded-xl border p-3 text-left transition duration-150 sm:p-3.5"
                            :class="
                                isActiveCompany()
                                    ? 'border-accent bg-accent-soft/50 shadow-sm ring-1 ring-accent/20'
                                    : 'border-line/80 bg-surface hover:border-accent/30 hover:bg-surface-muted/50'
                            "
                            :disabled="form.processing"
                            @click="switchToCompany"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                :class="
                                    isActiveCompany()
                                        ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300'
                                        : 'bg-surface-muted text-ink-muted group-hover:text-indigo-600 dark:group-hover:text-indigo-300'
                                "
                            >
                                <NavIcon name="companies" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="min-w-0 flex-1 font-semibold leading-snug text-ink">{{ companyLabel }}</span>
                                    <span
                                        v-if="companyCode"
                                        class="shrink-0 rounded-md border border-line/80 bg-surface-muted/80 px-1.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted"
                                        :class="isActiveCompany() ? 'border-accent/25 bg-accent-soft text-accent' : ''"
                                    >
                                        {{ companyCode }}
                                    </span>
                                    <span
                                        v-if="isActiveCompany()"
                                        class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                                    >
                                        {{ t('auth.contextActive') }}
                                    </span>
                                </span>
                                <span class="mt-1 block text-xs leading-relaxed text-ink-muted">
                                    {{ t('auth.contextCompanyHint') }}
                                </span>
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="!showPanelTabs || panelTab === 'stores'"
                        class="space-y-2"
                        :class="{ 'mt-2': !showPanelTabs && options.can_select_company }"
                    >
                        <p
                            v-if="showPanelTabs"
                            class="px-1 pb-1 text-[10px] font-bold uppercase tracking-wide text-ink-muted"
                        >
                            {{ t('auth.contextStoresTab') }}
                        </p>

                        <p
                            v-if="showStoreSearch && searchQuery.trim() && filteredStores.length === 0"
                            class="rounded-xl border border-dashed border-line px-4 py-5 text-center text-xs text-ink-muted"
                        >
                            {{ t('auth.noStoresMatch') }}
                        </p>

                        <button
                            v-for="store in filteredStores"
                            :key="store.id"
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-xl border p-3 text-left transition duration-150 sm:p-3.5"
                            :class="
                                isActiveStore(store.id)
                                    ? 'border-accent bg-accent-soft/50 shadow-sm ring-1 ring-accent/20'
                                    : 'border-line/80 bg-surface hover:border-accent/30 hover:bg-surface-muted/50'
                            "
                            :disabled="form.processing"
                            @click="switchToStore(store.id)"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                :class="
                                    isActiveStore(store.id)
                                        ? 'bg-accent-soft text-accent'
                                        : 'bg-surface-muted text-ink-muted group-hover:text-accent'
                                "
                            >
                                <NavIcon name="stores" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="block min-w-0 truncate font-semibold leading-snug text-ink">
                                        {{ store.store_name }}
                                    </span>
                                    <span
                                        class="shrink-0 rounded-md border border-line/80 bg-surface-muted/80 px-1.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted"
                                        :class="isActiveStore(store.id) ? 'border-accent/25 bg-accent-soft text-accent' : ''"
                                    >
                                        {{ store.store_code }}
                                    </span>
                                </span>
                            </span>
                            <span
                                v-if="isActiveStore(store.id)"
                                class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                            >
                                {{ t('auth.contextActive') }}
                            </span>
                        </button>
                    </div>
                </div>

                <div
                    class="shrink-0 border-t border-line/70 bg-surface-muted/30 px-4 py-2.5 pb-[max(0.625rem,env(safe-area-inset-bottom))] sm:px-5 sm:pb-2.5"
                    @click.stop
                >
                    <Link
                        :href="route('login-context.create')"
                        class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-xs font-semibold text-ink-muted transition hover:bg-surface hover:text-ink"
                        @click="dropdownOpen = false"
                    >
                        <span>{{ t('auth.manageContext') }}</span>
                        <NavIcon name="chevron-left" class="h-3.5 w-3.5 rotate-180 opacity-60" />
                    </Link>
                </div>
            </template>
        </Dropdown>

        <div
            v-else
            class="flex h-9 max-w-[10.5rem] items-center gap-2 rounded-lg border border-line bg-surface pl-1 pr-1.5 shadow-sm sm:max-w-[15rem] sm:pr-2 lg:max-w-sm"
            :title="`${triggerTitle} · ${triggerMeta}`"
        >
            <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md [&_svg]:h-4 [&_svg]:w-4"
                :class="
                    isCompanyScope
                        ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300'
                        : 'bg-accent-soft text-accent'
                "
            >
                <NavIcon :name="isCompanyScope ? 'companies' : 'stores'" />
            </span>
            <span class="min-w-0 truncate text-[13px] font-semibold leading-none text-ink">
                {{ triggerTitle }}
            </span>
            <span
                class="hidden shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase leading-none tracking-wider sm:inline-block"
                :class="
                    isCompanyScope
                        ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                        : 'bg-surface-muted font-mono text-ink-muted'
                "
            >
                {{ triggerMeta }}
            </span>
        </div>
    </div>
</template>
