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
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { PurchaseOrderRecord } from '@/types/purchaseOrder';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { can } = usePermissions();
const page = useModulePage('purchaseOrders');
const { t } = useI18n();
const { filter, submit } = useLocale();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'po_number', col: 'poNumber' },
    { key: 'supplier' },
    { key: 'grand_total', col: 'total' },
    { key: 'order_date', col: 'orderDate' },
]);

const props = defineProps<{
    purchaseOrders: Paginated<PurchaseOrderRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { search: string; company_id: string; store_id: string; status: string };
}>();

const search = ref(props.filters.search);
const storeId = ref(props.filters.store_id);
const status = ref(props.filters.status);
const showCompanyFilter = computed(() => props.companies.length > 1);
const filterQuery = computed(() => ({
    search: search.value || undefined,
    store_id: storeId.value || undefined,
    status: status.value || undefined,
}));
const hasActiveFilters = computed(() => !!search.value || !!storeId.value || !!status.value);
const countLabel = useRecordCountLabel(() => props.purchaseOrders.total, 'po');

const statusVariant: Record<string, 'neutral' | 'success' | 'warning' | 'danger'> = {
    draft: 'neutral',
    approved: 'warning',
    partially_received: 'warning',
    received: 'success',
    cancelled: 'danger',
};

const columns = computed(() => [
    lineCol.value,
    extraCols.value[0],
    extraCols.value[1],
    { key: 'store', label: t('common.store') },
    { key: 'status', label: t('common.status') },
    extraCols.value[2],
    extraCols.value[3],
]);

function applyFilters() {
    router.get(
        route('admin.purchase-orders.index'),
        { search: search.value || undefined, store_id: storeId.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

watch([search, storeId, status], applyFilters);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="purchaseOrders.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('purchasing.create')" #actions>
                    <Link :href="route('admin.purchase-orders.create')"><Button>{{ page.newButton }}</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="() => { search = ''; storeId = ''; status = ''; applyFilters(); }">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-model="storeId" class="!w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.store_name }}</option>
                </Select>
                <Select v-model="status" class="!w-44">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="draft">{{ filter('draft') }}</option>
                    <option value="approved">{{ filter('approved') }}</option>
                    <option value="partially_received">{{ filter('partiallyReceived') }}</option>
                    <option value="received">{{ filter('received') }}</option>
                    <option value="cancelled">{{ filter('cancelled') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState
                v-if="!purchaseOrders.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('purchasing.create') ? submit('createPo') : undefined"
                @action="can('purchasing.create') && router.visit(route('admin.purchase-orders.create'))"
            />

            <DataTable v-else :columns="columns" :rows="purchaseOrders.data" :paginated="purchaseOrders" pagination-route="admin.purchase-orders.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(purchaseOrders.from, index) }}</span>
                </template>
                <template #cell-po_number="{ row }">
                    <Link :href="route('admin.purchase-orders.edit', (row as PurchaseOrderRecord).id)" class="font-medium text-accent hover:underline">
                        {{ (row as PurchaseOrderRecord).po_number }}
                    </Link>
                </template>
                <template #cell-supplier="{ row }">{{ (row as PurchaseOrderRecord).supplier?.name ?? '—' }}</template>
                <template #cell-store="{ row }">{{ (row as PurchaseOrderRecord).store?.store_name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant[(row as PurchaseOrderRecord).status] ?? 'neutral'">
                        {{ (row as PurchaseOrderRecord).status.replace(/_/g, ' ') }}
                    </Badge>
                </template>
                <template #cell-grand_total="{ row }">₱{{ Number((row as PurchaseOrderRecord).grand_total).toFixed(2) }}</template>
                <template #actions="{ row }">
                    <div class="flex gap-2">
                        <Link :href="route('admin.purchase-orders.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.view') }}</Link>
                        <Link
                            v-if="['approved', 'partially_received'].includes(row.status) && can('purchasing.create')"
                            :href="route('admin.purchase-orders.receive.create', row.id)"
                            class="text-sm text-accent hover:underline"
                        >{{ t('common.receive') }}</Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
