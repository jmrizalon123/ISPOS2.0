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
import { usePermissions } from '@/Composables/usePermissions';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { can } = usePermissions();
const page = useModulePage('stock');
const { t } = useI18n();

interface InventoryRow {
    id: string;
    qty: string;
    store?: { id: string; store_name: string; store_code: string };
    product?: { id: string; sku: string; name: string; warning_qty: string | null; ideal_qty: string | null };
}

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'sku' },
    { key: 'name', col: 'product' },
    { key: 'qty', col: 'onHand' },
    { key: 'warning_qty', col: 'warning' },
]);

const props = defineProps<{
    inventories: Paginated<InventoryRow>;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { search: string; company_id: string; store_id: string };
}>();

const search = ref(props.filters.search);
const companyId = ref(props.filters.company_id);
const storeId = ref(props.filters.store_id);
const showCompanyFilter = computed(() => props.companies.length > 1);
const filterQuery = computed(() => ({
    search: search.value || undefined,
    company_id: companyId.value || undefined,
    store_id: storeId.value || undefined,
}));
const hasActiveFilters = computed(() => !!search.value || !!companyId.value || !!storeId.value);
const countLabel = useRecordCountLabel(() => props.inventories.total, 'balance');

const columns = computed(() => [
    lineCol.value,
    { key: 'store', label: t('common.store') },
    ...extraCols.value,
    { key: 'status', label: t('common.status') },
]);

function applyFilters() {
    router.get(
        route('admin.inventory.stock.index'),
        {
            search: search.value || undefined,
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    companyId.value = '';
    storeId.value = '';
    applyFilters();
}

watch([search, companyId, storeId], applyFilters);

function stockStatus(row: InventoryRow): 'ok' | 'low' | 'out' {
    const qty = parseFloat(row.qty) || 0;
    const warning = row.product?.warning_qty != null ? parseFloat(row.product.warning_qty) : null;
    if (qty <= 0) return 'out';
    if (warning != null && qty <= warning) return 'low';
    return 'ok';
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="inventories.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="can('inventory.adjust')" #actions>
                    <Link :href="route('admin.inventory.adjustments.create')">
                        <span class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white dark:text-zinc-950">Adjust stock</span>
                    </Link>
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
            </IndexToolbar>

            <EmptyState
                v-if="!inventories.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
            />

            <DataTable v-else :columns="columns" :rows="inventories.data" :paginated="inventories" pagination-route="admin.inventory.stock.index" :pagination-query="filterQuery" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(inventories.from, index) }}</span>
                </template>
                <template #cell-store="{ row }">
                    {{ (row as InventoryRow).store?.store_name ?? '—' }}
                </template>
                <template #cell-sku="{ row }">
                    {{ (row as InventoryRow).product?.sku ?? '—' }}
                </template>
                <template #cell-name="{ row }">
                    {{ (row as InventoryRow).product?.name ?? '—' }}
                </template>
                <template #cell-qty="{ row }">
                    <span class="font-medium tabular-nums">{{ (row as InventoryRow).qty }}</span>
                </template>
                <template #cell-warning_qty="{ row }">
                    {{ (row as InventoryRow).product?.warning_qty ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="stockStatus(row as InventoryRow) === 'ok' ? 'success' : stockStatus(row as InventoryRow) === 'low' ? 'warning' : 'danger'">
                        {{ stockStatus(row as InventoryRow) === 'ok' ? 'OK' : stockStatus(row as InventoryRow) === 'low' ? 'Low' : 'Out' }}
                    </Badge>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
