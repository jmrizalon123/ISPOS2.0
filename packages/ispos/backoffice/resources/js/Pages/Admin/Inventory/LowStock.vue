<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { paginatedRowNumber, useLineTableColumn } from '@/Composables/useTableColumns';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { can } = usePermissions();
const page = useModulePage('lowStock');
const { t } = useI18n();

interface InventoryRow {
    id: string;
    qty: string;
    store?: { id: string; store_name: string; store_code: string };
    product?: {
        id: string;
        sku: string;
        name: string;
        warning_qty: string | null;
        ideal_qty: string | null;
        unit?: { symbol: string };
    };
}

const lineCol = useLineTableColumn();

const props = defineProps<{
    inventories: Paginated<InventoryRow>;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { search: string; company_id: string; store_id: string };
    summary: { total: number };
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
const alertCountLabel = useRecordCountLabel(() => props.summary.total, 'alert');
const itemCountLabel = useRecordCountLabel(() => props.inventories.total, 'item');

const columns = computed(() => [
    lineCol.value,
    { key: 'store', label: t('common.store') },
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Product' },
    { key: 'qty', label: 'QTY' },
    { key: 'warning_qty', label: 'Warning Qty' },
    { key: 'ideal_qty', label: 'Ideal Qty' },
    { key: 'status', label: t('common.status') },
]);

function applyFilters() {
    router.get(
        route('admin.inventory.low-stock'),
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

function stockStatus(row: InventoryRow): 'low' | 'out' {
    const qty = parseFloat(row.qty) || 0;
    return qty <= 0 ? 'out' : 'low';
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
                :back-href="route('admin.inventory.stock.index')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge v-if="summary.total > 0" variant="warning">{{ alertCountLabel }}</Badge>
                    <Badge variant="neutral">{{ itemCountLabel }}</Badge>
                </template>
                <template #actions>
                    <Link v-if="can('inventory.adjust')" :href="route('admin.inventory.adjustments.create')">
                        <Button variant="secondary">Adjust stock</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <Alert v-if="summary.total > 0" class="shrink-0" variant="warning">
                {{ summary.total }} store product{{ summary.total === 1 ? '' : 's' }} need restocking attention.
            </Alert>

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

            <DataTable v-else :columns="columns" :rows="inventories.data" :paginated="inventories" pagination-route="admin.inventory.low-stock" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
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
                    <Link
                        v-if="can('products.update') && (row as InventoryRow).product"
                        :href="route('admin.products.edit', (row as InventoryRow).product!.id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ (row as InventoryRow).product!.name }}
                    </Link>
                    <span v-else>{{ (row as InventoryRow).product?.name ?? '—' }}</span>
                </template>
                <template #cell-qty="{ row }">
                    {{ (row as InventoryRow).qty }}
                    <span v-if="(row as InventoryRow).product?.unit?.symbol" class="text-ink-muted">
                        {{ (row as InventoryRow).product?.unit?.symbol }}
                    </span>
                </template>
                <template #cell-warning_qty="{ row }">
                    {{ (row as InventoryRow).product?.warning_qty ?? '—' }}
                </template>
                <template #cell-ideal_qty="{ row }">
                    {{ (row as InventoryRow).product?.ideal_qty ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="stockStatus(row as InventoryRow) === 'out' ? 'danger' : 'warning'">
                        {{ stockStatus(row as InventoryRow) === 'out' ? 'Out of stock' : 'Low stock' }}
                    </Badge>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
