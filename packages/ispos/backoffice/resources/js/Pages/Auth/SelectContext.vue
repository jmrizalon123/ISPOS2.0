<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import Input from '@/Components/ui/Input.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    options: {
        can_select_company: boolean;
        company: { id: string; name: string; company_code: string; display_name: string | null } | null;
        stores: Array<{ id: string; store_name: string; store_code: string }>;
    };
    defaultScope: 'company' | 'store';
    defaultStoreId: string | null;
}>();

const { t } = useLocale();
const page = usePage();
const searchQuery = ref('');

const form = useForm({
    scope: props.defaultScope,
    store_id: props.defaultStoreId ?? '',
});

const companyLabel = computed(() => {
    const company = props.options.company;
    if (!company) {
        return t('auth.contextCompany');
    }

    return company.display_name || company.name;
});

const choiceCount = computed(() => props.options.stores.length + (props.options.can_select_company ? 1 : 0));

const filteredStores = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return props.options.stores;
    }

    return props.options.stores.filter((store) =>
        store.store_name.toLowerCase().includes(query)
        || store.store_code.toLowerCase().includes(query),
    );
});

const showStoreSearch = computed(() => props.options.stores.length >= 2);

const appName = computed(() => page.props.app?.name ?? 'iSPOS');

function isCompanySelected() {
    return form.scope === 'company';
}

function isStoreSelected(storeId: string) {
    return form.scope === 'store' && form.store_id === storeId;
}

function selectCompany() {
    form.scope = 'company';
    form.store_id = '';
}

function selectStore(storeId: string) {
    form.scope = 'store';
    form.store_id = storeId;
}

function submit() {
    form.post(route('login-context.store'));
}
</script>

<template>
    <AuthLayout
        viewport-fit
        :welcome-title="t('auth.contextWelcomeTitle')"
        :welcome-subtitle="t('auth.contextWelcomeSubtitle', { app: appName })"
        panel-class="mx-auto max-w-[480px]"
        card-class="!p-0"
    >
        <Head :title="t('auth.selectContextTitle')" />

        <div class="flex h-full min-h-0 flex-1 flex-col">
            <div class="shrink-0 space-y-3 border-b border-line px-5 py-4 sm:px-8 sm:py-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-display text-lg font-bold tracking-tight text-ink sm:text-xl">
                        {{ t('auth.selectContextTitle') }}
                    </h2>
                    <span
                        class="shrink-0 rounded-full border border-line bg-surface-muted px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-ink-muted"
                    >
                        {{ t('auth.contextChoiceCount', { count: choiceCount }) }}
                    </span>
                </div>

                <div v-if="showStoreSearch" class="relative [&_input]:!pl-9">
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

            <form class="flex min-h-0 flex-1 flex-col overflow-hidden" @submit.prevent="submit">
                <div class="relative min-h-0 flex-1 overflow-hidden">
                    <div class="scrollbar-visible h-full space-y-2.5 overflow-y-auto overscroll-contain px-5 py-3 sm:px-8 sm:py-4">
                        <button
                            v-if="options.can_select_company"
                            type="button"
                            class="group relative flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all duration-200 sm:gap-4 sm:p-[1.125rem]"
                            :class="
                                isCompanySelected()
                                    ? 'border-accent bg-accent-soft/60 shadow-sm ring-1 ring-accent/20'
                                    : 'border-line bg-surface hover:border-accent/35 hover:bg-surface-muted/40'
                            "
                            @click="selectCompany"
                        >
                            <span
                                class="mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-200"
                                :class="
                                    isCompanySelected()
                                        ? 'border-accent bg-accent'
                                        : 'border-zinc-300 group-hover:border-accent/50 dark:border-zinc-600'
                                "
                            >
                                <span
                                    v-if="isCompanySelected()"
                                    class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-950"
                                />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="block font-semibold leading-snug text-ink">{{ companyLabel }}</span>
                                    <span
                                        v-if="options.company?.company_code"
                                        class="shrink-0 rounded-md border border-line/80 bg-surface-muted/80 px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted"
                                        :class="isCompanySelected() ? 'border-accent/25 bg-accent-soft text-accent' : ''"
                                    >
                                        {{ options.company.company_code }}
                                    </span>
                                    <span
                                        v-else
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-line/80 bg-surface-muted/80 text-ink-muted transition group-hover:border-accent/20 group-hover:text-accent"
                                        :class="isCompanySelected() ? 'border-accent/25 bg-accent-soft text-accent' : ''"
                                    >
                                        <NavIcon name="companies" class="h-4 w-4" />
                                    </span>
                                </span>
                            </span>
                        </button>

                        <p
                            v-if="showStoreSearch && searchQuery.trim() && filteredStores.length === 0"
                            class="rounded-xl border border-dashed border-line px-4 py-6 text-center text-sm text-ink-muted"
                        >
                            {{ t('auth.noStoresMatch') }}
                        </p>

                        <button
                            v-for="store in filteredStores"
                            :key="store.id"
                            type="button"
                            class="group relative flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all duration-200 sm:gap-4 sm:p-[1.125rem]"
                            :class="
                                isStoreSelected(store.id)
                                    ? 'border-accent bg-accent-soft/60 shadow-sm ring-1 ring-accent/20'
                                    : 'border-line bg-surface hover:border-accent/35 hover:bg-surface-muted/40'
                            "
                            @click="selectStore(store.id)"
                        >
                            <span
                                class="mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-200"
                                :class="
                                    isStoreSelected(store.id)
                                        ? 'border-accent bg-accent'
                                        : 'border-zinc-300 group-hover:border-accent/50 dark:border-zinc-600'
                                "
                            >
                                <span
                                    v-if="isStoreSelected(store.id)"
                                    class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-950"
                                />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="block min-w-0 truncate font-semibold leading-snug text-ink">
                                        {{ store.store_name }}
                                    </span>
                                    <span
                                        class="shrink-0 rounded-md border border-line/80 bg-surface-muted/80 px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted"
                                        :class="isStoreSelected(store.id) ? 'border-accent/25 bg-accent-soft text-accent' : ''"
                                    >
                                        {{ store.store_code }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="choiceCount > 3"
                        class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-surface to-transparent"
                    />
                </div>

                <div class="shrink-0 space-y-3 border-t border-line bg-surface px-5 py-3 sm:space-y-4 sm:px-8 sm:py-4">
                    <div
                        v-if="form.errors.scope || form.errors.store_id"
                        class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300"
                    >
                        <p v-if="form.errors.scope">{{ form.errors.scope }}</p>
                        <p v-if="form.errors.store_id">{{ form.errors.store_id }}</p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        size="lg"
                        :disabled="form.processing || (form.scope === 'store' && !form.store_id)"
                    >
                        {{ form.processing ? t('auth.contextContinuing') : t('auth.contextContinue') }}
                    </Button>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
