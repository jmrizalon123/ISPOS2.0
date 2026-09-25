<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('movements');
const { t } = useI18n();

interface MovementRow {
    id: string;
    movement_type: string;
    quantity_delta: string;
    qty_before: string;
    qty_after: string;
    reason: string | null;
    created_at: string;
    store?: { store_name: string; store_code: string };
    product?: { sku: string; name: string };
    user?: { name: string };
    sale?: { id: string; sale_number: string };
}

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'created_at', col: 'date' },
    { key: 'product' },
    { key: 'movement_type', col: 'movementType' },
    { key: 'quantity_delta', col: 'delta' },
    { key: 'qty_after', col: 'balance' },
    { key: 'user' },
]);

const props = defineProps<{
    movements: Paginated<MovementRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    movementTypes: string[];
    filters: { search: string; company_id: string; store_id: string; movement_type: string };
}>();

const search = ref(props.filters.search);
const companyId = ref(props.filters.company_id);
const storeId = ref(props.filters.store_id);
const movementType = ref(props.filters.movement_type);
const showCompanyFilter = computed(() => props.companies.length > 1);
const filterQuery = computed(() => ({
    search: search.value || undefined,
    company_id: companyId.value || undefined,
    store_id: storeId.value || undefined,
    movement_type: movementType.value || undefined,
}));
const hasActiveFilters = computed(() => !!search.value || !!companyId.value || !!storeId.value || !!movementType.value);
const countLabel = useRecordCountLabel(() => props.movements.total, 'movement');

const columns = computed(() => [
    lineCol.value,
    extraCols.value[0],
    { key: 'store', label: t('common.store') },
    ...extraCols.value.slice(1),
]);

function applyFilters() {
    router.get(
        route('admin.inventory.movements.index'),
        {
            search: search.value || undefined,
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
            movement_type: movementType.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    companyId.value = '';
    storeId.value = '';
    movementType.value = '';
    applyFilters();
}

watch([search, companyId, storeId, movementType], applyFilters);

function formatDate(value: string): string {
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="movements.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-model="storeId" class="!w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.store_name }}
                    </option>
                </Select>
                <Select v-model="movementType" class="!w-44">
                    <option value="">All types</option>
                    <option v-for="type in movementTypes" :key="type" :value="type">
                        {{ type.replace(/_/g, ' ') }}
                    </option>
                </Select>
            </IndexToolbar>

            <EmptyState v-if="!movements.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="movements.data" :paginated="movements" pagination-route="admin.inventory.movements.index" :pagination-query="filterQuery" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(movements.from, index) }}</span>
                </template>
                <template #cell-created_at="{ row }">
                    {{ formatDate((row as MovementRow).created_at) }}
                </template>
                <template #cell-store="{ row }">
                    {{ (row as MovementRow).store?.store_name ?? '—' }}
                </template>
                <template #cell-product="{ row }">
                    <div>
                        <p class="font-medium">{{ (row as MovementRow).product?.name }}</p>
                        <p class="text-xs text-ink-muted">{{ (row as MovementRow).product?.sku }}</p>
                    </div>
                </template>
                <template #cell-movement_type="{ row }">
                    <Badge variant="neutral">{{ (row as MovementRow).movement_type.replace(/_/g, ' ') }}</Badge>
                </template>
                <template #cell-quantity_delta="{ row }">
                    <span :class="parseFloat((row as MovementRow).quantity_delta) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ (row as MovementRow).quantity_delta }}
                    </span>
                </template>
                <template #cell-qty_after="{ row }">
                    {{ (row as MovementRow).qty_after }}
                </template>
                <template #cell-user="{ row }">
                    {{ (row as MovementRow).user?.name ?? '—' }}
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
