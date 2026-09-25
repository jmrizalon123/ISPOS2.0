<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('transfers');
const { t } = useI18n();

interface TransferRow {
    id: string;
    transfer_no: string;
    transfer_number: string;
    transfer_type: string;
    priority: string;
    status: string;
    transferred_at: string | null;
    transfer_date: string | null;
    line_count: number;
    from_store: { id: string; store_name: string; store_code: string } | null;
    to_store: { id: string; store_name: string; store_code: string } | null;
    from_warehouse: { id: string; store_name: string; store_code: string } | null;
    to_warehouse: { id: string; store_name: string; store_code: string } | null;
}

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'transfer_number', col: 'transferNumber' },
    { key: 'transfer_type', col: 'type' },
    { key: 'from_store', col: 'from' },
    { key: 'to_store', col: 'to' },
    { key: 'line_count', col: 'items' },
    { key: 'transferred_at', col: 'date' },
]);

const props = defineProps<{
    transfers: Paginated<TransferRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; search: string };
    canCreate: boolean;
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
const hasActiveFilters = computed(() => !!search.value || !!storeId.value);
const countLabel = useRecordCountLabel(() => props.transfers.total, 'transfer');

const columns = computed(() => [
    lineCol.value,
    ...extraCols.value,
    { key: 'status', label: t('common.status') },
    { key: 'actions', label: '' },
]);

function applyFilters() {
    router.get(
        route('admin.inventory.transfers.index'),
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
    storeId.value = '';
    applyFilters();
}

watch([search, companyId, storeId], applyFilters);

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function locationName(row: TransferRow, side: 'from' | 'to'): string {
    const warehouse = side === 'from' ? row.from_warehouse : row.to_warehouse;
    const store = side === 'from' ? row.from_store : row.to_store;
    return warehouse?.store_name || store?.store_name || '—';
}

function statusVariant(status: string): 'neutral' | 'success' | 'warning' | 'danger' | 'accent' {
    if (status === 'received') return 'success';
    if (status === 'cancelled' || status === 'rejected') return 'danger';
    if (status === 'in_transit' || status === 'processing' || status === 'partially_received') return 'warning';
    if (status === 'approved') return 'accent';
    return 'neutral';
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="transfers.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template #actions>
                    <Link v-if="canCreate" :href="route('admin.inventory.transfers.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
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

            <EmptyState v-if="!transfers.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="transfers.data" :paginated="transfers" pagination-route="admin.inventory.transfers.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(transfers.from, index) }}</span>
                </template>
                <template #cell-transfer_number="{ row }">
                    {{ (row as TransferRow).transfer_no || (row as TransferRow).transfer_number }}
                </template>
                <template #cell-transfer_type="{ row }">
                    <span class="capitalize">{{ (row as TransferRow).transfer_type.replaceAll('_', ' ') }}</span>
                </template>
                <template #cell-from_store="{ row }">
                    {{ locationName(row as TransferRow, 'from') }}
                </template>
                <template #cell-to_store="{ row }">
                    {{ locationName(row as TransferRow, 'to') }}
                </template>
                <template #cell-line_count="{ row }">
                    <span class="tabular-nums">{{ (row as TransferRow).line_count }}</span>
                </template>
                <template #cell-transferred_at="{ row }">
                    {{ formatDate((row as TransferRow).transferred_at) }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant((row as TransferRow).status)">{{ (row as TransferRow).status.replaceAll('_', ' ') }}</Badge>
                </template>
                <template #cell-actions="{ row }">
                    <Link
                        :href="route('admin.inventory.transfers.show', (row as TransferRow).id)"
                        class="text-sm font-medium text-accent hover:underline"
                    >
                        {{ t('common.view') }}
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
