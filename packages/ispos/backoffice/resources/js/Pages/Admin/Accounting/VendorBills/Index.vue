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
import { formatReportMoney } from '@/Composables/useReportFilters';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { VendorBillRecord } from '@/types/vendorBill';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const page = useModulePage('vendorBills');
const { t } = useI18n();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'bill_date', col: 'billDate' },
    { key: 'bill_number', col: 'billNumber' },
    { key: 'supplier' },
    { key: 'amount_due', col: 'amountDue' },
    { key: 'amount_paid', col: 'paid' },
]);

const props = defineProps<{
    bills: Paginated<VendorBillRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.vendor-bills.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.bills.total, 'bill');

const columns = computed(() => [
    lineCol.value,
    ...extraCols.value,
    { key: 'status', label: t('common.status') },
]);

function balanceDue(row: VendorBillRecord): string {
    return (Number(row.amount_due) - Number(row.amount_paid)).toFixed(2);
}

function statusVariant(status: string) {
    if (status === 'paid') return 'success';
    if (status === 'partial') return 'warning';
    return 'neutral';
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="bills.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template #actions>
                    <Link :href="route('admin.supplier-payments.index')"><Badge variant="neutral">Supplier payments →</Badge></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" type="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-model="status" class="!w-36">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="open">Open</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </Select>
            </IndexToolbar>

            <DataTable v-if="bills.data.length" :columns="columns" :rows="bills.data" :paginated="bills" pagination-route="admin.vendor-bills.index" :pagination-query="filterQuery" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(bills.from, index) }}</span>
                </template>
                <template #cell-supplier="{ row }">{{ row.supplier?.name ?? '—' }}</template>
                <template #cell-amount_due="{ row }">{{ formatReportMoney(String(row.amount_due)) }}</template>
                <template #cell-amount_paid="{ row }">{{ formatReportMoney(balanceDue(row)) }} due</template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant(row.status)">{{ row.status }}</Badge>
                </template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
