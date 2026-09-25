<script setup lang="ts">
import { computed } from 'vue';
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
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { paginatedRowNumber, useLineTableColumn } from '@/Composables/useTableColumns';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface OnlineOrderRow {
    id: string;
    order_number: string;
    guest_name: string;
    guest_phone: string;
    fulfillment_type: string;
    status: string;
    payment_method: string;
    grand_total: string | number;
    currency: string;
    created_at: string | null;
    store?: { id: string; store_name: string; store_code: string } | null;
    company?: { id: string; name: string; display_name?: string | null } | null;
}

const page = useModulePage('onlineOrders');
const { t } = useI18n();
const lineCol = useLineTableColumn();

const props = defineProps<{
    orders: Paginated<OnlineOrderRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string }>;
    filters: { search: string; company_id: string; store_id: string; status: string };
}>();

const { search, companyId, storeId, status, showCompanyFilter, showStoreFilter, filteredStores, hasActiveFilters, clearFilters, filterQuery } =
    useCatalogIndexFilters(
        'admin.online-orders.index',
        props.filters,
        ['search', 'company_id', 'store_id', 'status'],
        props.companies,
        props.stores,
    );

const countLabel = useRecordCountLabel(() => props.orders.total, 'order');

const statusVariant: Record<string, 'neutral' | 'success' | 'warning' | 'danger'> = {
    pending: 'warning',
    accepted: 'neutral',
    preparing: 'neutral',
    ready: 'success',
    completed: 'success',
    cancelled: 'danger',
    rejected: 'danger',
};

const columns = computed(() => [
    lineCol.value,
    { key: 'order_number', label: 'Order #' },
    { key: 'guest', label: 'Customer' },
    { key: 'store', label: t('common.store') },
    { key: 'fulfillment_type', label: 'Fulfillment' },
    { key: 'status', label: t('common.status') },
    { key: 'grand_total', label: 'Total' },
    { key: 'created_at', label: 'Placed' },
]);

function money(amount: string | number, currency = 'PHP'): string {
    return `${currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="orders.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-if="showStoreFilter" v-model="storeId" class="!w-44">
                    <option value="">All stores</option>
                    <option v-for="store in filteredStores" :key="store.id" :value="store.id">
                        {{ store.store_name }}
                    </option>
                </Select>
                <Select v-model="status" class="!w-44">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </Select>
            </IndexToolbar>

            <EmptyState v-if="!orders.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable
                v-else
                :columns="columns"
                :rows="orders.data"
                :paginated="orders"
                pagination-route="admin.online-orders.index"
                :pagination-query="filterQuery"
                sticky-actions
                viewport-fit
                compact
            >
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(orders.from, index) }}</span>
                </template>
                <template #cell-order_number="{ row }">
                    <Link
                        :href="route('admin.online-orders.show', (row as OnlineOrderRow).id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ (row as OnlineOrderRow).order_number }}
                    </Link>
                </template>
                <template #cell-guest="{ row }">
                    <div>
                        <div>{{ (row as OnlineOrderRow).guest_name }}</div>
                        <div class="text-xs text-ink-muted">{{ (row as OnlineOrderRow).guest_phone }}</div>
                    </div>
                </template>
                <template #cell-store="{ row }">
                    {{ (row as OnlineOrderRow).store?.store_name ?? '—' }}
                </template>
                <template #cell-fulfillment_type="{ row }">
                    {{ (row as OnlineOrderRow).fulfillment_type.replace('_', ' ') }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant[(row as OnlineOrderRow).status] ?? 'neutral'">
                        {{ (row as OnlineOrderRow).status }}
                    </Badge>
                </template>
                <template #cell-grand_total="{ row }">
                    {{ money((row as OnlineOrderRow).grand_total, (row as OnlineOrderRow).currency) }}
                </template>
                <template #cell-created_at="{ row }">
                    {{ (row as OnlineOrderRow).created_at ? new Date((row as OnlineOrderRow).created_at!).toLocaleString() : '—' }}
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.online-orders.show', row.id)" class="text-sm text-accent hover:underline">
                        View
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
