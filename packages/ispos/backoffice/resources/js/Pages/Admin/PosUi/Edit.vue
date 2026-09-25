<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import Select from '@/Components/ui/Select.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import type { PosUiLayout, PosUiLayoutOverride } from '@/Utils/posUiLayout';
import { layoutLabel, POS_UI_LAYOUT_OPTIONS } from '@/Utils/posUiLayout';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const modulePage = useModulePage('posUi');
const { t, field, tab: tabLabel, hint } = useLocale();

const props = defineProps<{
    companyId: string | null;
    storeId: string | null;
    companies: Array<{ id: string; name: string; company_code?: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string; store_category: string | null }>;
    layoutOptions: Array<{ value: string; label: string; description: string }>;
    categoryDefaults: Record<string, PosUiLayout>;
    companyUiLayout: PosUiLayoutOverride;
    storeUiLayout: PosUiLayoutOverride;
    resolvedStoreLayout: PosUiLayout | null;
}>();

const page = usePage();
const tab = ref<'company' | 'store'>(props.storeId ? 'store' : 'company');
const companyId = ref(props.companyId ?? '');
const storeId = ref(props.storeId ?? '');

watch([companyId, storeId], () => {
    router.get(
        route('admin.pos-ui.edit'),
        {
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

const form = useForm({
    scope: tab.value,
    scope_id: tab.value === 'store' ? storeId.value : companyId.value,
    ui_layout: (tab.value === 'store' ? props.storeUiLayout : props.companyUiLayout) as PosUiLayoutOverride,
});

watch(tab, (value) => {
    form.scope = value;
    form.scope_id = value === 'store' ? storeId.value : companyId.value;
    form.ui_layout = (value === 'store' ? props.storeUiLayout : props.companyUiLayout) as PosUiLayoutOverride;
});

watch(
    () => [props.companyUiLayout, props.storeUiLayout, tab.value],
    () => {
        form.ui_layout = (tab.value === 'store' ? props.storeUiLayout : props.companyUiLayout) as PosUiLayoutOverride;
        form.scope_id = tab.value === 'store' ? storeId.value : companyId.value;
    },
);

const selectedStore = computed(() => props.stores.find((store) => store.id === storeId.value) ?? null);

const previewLayout = computed<PosUiLayout>(() => {
    if (tab.value === 'store' && form.ui_layout !== 'auto') {
        return form.ui_layout;
    }

    if (tab.value === 'store' && props.resolvedStoreLayout) {
        return props.resolvedStoreLayout;
    }

    if (form.ui_layout !== 'auto') {
        return form.ui_layout;
    }

    return 'retail';
});

async function submit() {
    if (!(await confirmSave(t('common.save'), t('messages.posUiSettings')))) {
        return;
    }

    form.scope = tab.value;
    form.scope_id = tab.value === 'store' ? storeId.value : companyId.value;
    form.put(route('admin.pos-ui.update'));
}

const categoryRows = computed(() =>
    Object.entries(props.categoryDefaults).map(([category, layout]) => ({
        category,
        layout,
        label: layoutLabel(layout),
    })),
);
</script>

<template>
    <Head :title="modulePage.header" />
    <AppLayout>
        <template #header>{{ modulePage.header }}</template>
        <template #subheader>{{ modulePage.subheader }}</template>

        <div class="index-page mx-auto flex max-w-5xl flex-col gap-2 p-2 sm:p-3">
            <IndexPageHeader
                :back-href="route('dashboard')"
                :eyebrow="modulePage.eyebrow"
                :title="modulePage.title"
                :description="modulePage.description"
            >
                <template #meta>
                    <Badge variant="accent">{{ t('messages.preview') }} {{ layoutLabel(previewLayout) }}</Badge>
                </template>
            </IndexPageHeader>

            <Alert v-if="page.props.flash?.success" variant="success">{{ page.props.flash.success }}</Alert>

            <Card :title="modulePage.title">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-ink-muted">{{ t('common.company') }}</label>
                        <Select v-model="companyId">
                            <option value="">{{ field('selectCompany') }}</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.display_name || company.name }}
                            </option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-ink-muted">{{ field('storeOptional') }}</label>
                        <Select v-model="storeId">
                            <option value="">{{ field('allStoresCompanyDefault') }}</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                {{ store.store_name }} ({{ store.store_code }})
                            </option>
                        </Select>
                    </div>
                </div>
            </Card>

            <Card>
            <Tabs
                v-model="tab"
                :tabs="[
                    { key: 'company', label: tabLabel('companyDefault') },
                    { key: 'store', label: tabLabel('storeOverride') },
                ]"
            >
            <div :title="modulePage.title">
                <p class="mb-4 text-sm text-ink-muted">
                    <template v-if="tab === 'company'">
                        {{ hint('posUiCompanyScope') }}
                    </template>
                    <template v-else>
                        {{ t('hints.posUiStoreScope', {
                            store: selectedStore?.store_name ?? t('messages.selectedStore'),
                            category: selectedStore?.store_category
                                ? t('hints.posUiStoreCategory', { category: selectedStore.store_category })
                                : '',
                        }) }}
                    </template>
                </p>

                <div class="grid gap-3">
                    <label
                        v-for="option in layoutOptions"
                        :key="option.value"
                        class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition hover:border-accent/40 hover:bg-accent-soft/30"
                        :class="form.ui_layout === option.value ? 'border-accent bg-accent-soft/40 ring-1 ring-accent/20' : ''"
                    >
                        <input v-model="form.ui_layout" type="radio" class="mt-1" :value="option.value" />
                        <span>
                            <span class="block text-sm font-semibold text-ink">{{ option.label }}</span>
                            <span class="mt-0.5 block text-xs text-ink-muted">{{ option.description }}</span>
                        </span>
                    </label>
                </div>

                <div v-if="tab === 'store' && form.ui_layout === 'auto' && resolvedStoreLayout" class="mt-4 rounded-lg bg-surface-muted px-4 py-3 text-sm text-ink-muted">
                    {{ t('hints.posUiAutoResolve', { layout: layoutLabel(resolvedStoreLayout) }) }}
                </div>

                <div class="mt-6 flex justify-end">
                    <Button :disabled="form.processing || (tab === 'store' && !storeId) || (tab === 'company' && !companyId)" @click="submit">
                        {{ t('messages.savePosUi') }}
                    </Button>
                </div>
            </div>
            </Tabs>
            </Card>

            <Card :title="modulePage.title">
                <p class="mb-4 text-sm text-ink-muted">
                    {{ hint('posUiCategoryDefaults') }}
                </p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="row in categoryRows"
                        :key="row.category"
                        class="flex items-center justify-between rounded-lg border border-line-subtle bg-surface-muted px-3 py-2 text-sm"
                    >
                        <span class="capitalize text-ink">{{ row.category }}</span>
                        <Badge variant="neutral">{{ row.label }}</Badge>
                    </div>
                </div>
            </Card>

            <Card :title="modulePage.title">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="option in POS_UI_LAYOUT_OPTIONS.filter((item) => item.value !== 'auto')"
                        :key="option.value"
                        class="overflow-hidden rounded-xl border border-line"
                        :class="previewLayout === option.value ? 'ring-2 ring-accent' : ''"
                    >
                        <div
                            class="pos-ui-preview-bar h-16"
                            :data-pos-layout="option.value"
                        />
                        <div class="p-3">
                            <p class="text-sm font-semibold text-ink">{{ option.label }}</p>
                            <p class="mt-1 text-xs text-ink-muted">{{ option.description }}</p>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.pos-ui-preview-bar {
    background: linear-gradient(135deg, var(--preview-primary, #2563eb), var(--preview-checkout, #0d9488));
}

.pos-ui-preview-bar[data-pos-layout='retail'] {
    --preview-primary: #2563eb;
    --preview-checkout: #0d9488;
}

.pos-ui-preview-bar[data-pos-layout='wholesale'] {
    --preview-primary: #4f46e5;
    --preview-checkout: #4338ca;
}

.pos-ui-preview-bar[data-pos-layout='grocery'] {
    --preview-primary: #059669;
    --preview-checkout: #047857;
}

.pos-ui-preview-bar[data-pos-layout='pharmacy'] {
    --preview-primary: #0284c7;
    --preview-checkout: #0369a1;
}

.pos-ui-preview-bar[data-pos-layout='restaurant'] {
    --preview-primary: #f59e0b;
    --preview-checkout: #d97706;
}

.pos-ui-preview-bar[data-pos-layout='cafe'] {
    --preview-primary: #d97706;
    --preview-checkout: #b45309;
}

.pos-ui-preview-bar[data-pos-layout='generic'] {
    --preview-primary: #64748b;
    --preview-checkout: #475569;
}
</style>
