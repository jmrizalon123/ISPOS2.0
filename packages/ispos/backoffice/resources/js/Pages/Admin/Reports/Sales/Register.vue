<script setup lang="ts">
import { computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';
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
import { formatReportMoney, useReportFilters } from '@/Composables/useReportFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { paginatedRowNumber, useLineTableColumn } from '@/Composables/useTableColumns';
import type { Paginated } from '@/types';
import type { SalesRegisterRow } from '@/types/salesReport';
import { Head, Link } from '@inertiajs/vue3';

const page = useModulePage('salesRegister');
const { t, column, filter } = useLocale();

const lineCol = useLineTableColumn();

const props = defineProps<{
    sales: Paginated<SalesRegisterRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string; search: string };
    canExport: boolean;
}>();

const { companyId, storeId, dateFrom, dateTo, status, search, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } =
    useReportFilters('admin.reports.sales-register.index', props.filters, props.companies, ['search']);

const countLabel = useRecordCountLabel(() => props.sales.total, 'sale');

const columns = computed(() => [
    lineCol.value,
    { key: 'completed_at', label: column('date'), minWidth: 168, class: 'whitespace-nowrap', sortable: false },
    { key: 'sale_number', label: column('saleNumber'), minWidth: 180, class: 'whitespace-nowrap', sortable: false },
    { key: 'store', label: t('common.store'), minWidth: 180, class: 'whitespace-nowrap', sortable: false },
    { key: 'user', label: column('cashier'), minWidth: 160, class: 'whitespace-nowrap', sortable: false },
    { key: 'customer', label: column('customer'), minWidth: 140, class: 'whitespace-nowrap', sortable: false },
    { key: 'status', label: t('common.status'), minWidth: 110, sortable: false },
    { key: 'grand_total', label: column('total'), minWidth: 112, class: 'whitespace-nowrap text-right tabular-nums', sortable: false },
]);

function exportUrl(): string {
    return route('admin.reports.sales-register.export', {
        company_id: props.filters.company_id || undefined,
        store_id: props.filters.store_id || undefined,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        status: props.filters.status,
    });
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="sales.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                :back-href="route('admin.reports.sales-summary.index', filters)"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template #actions>
                    <a v-if="canExport" :href="exportUrl()"><Button variant="secondary">{{ t('messages.exportCsv') }}</Button></a>
                    <Link :href="route('admin.reports.sales-summary.index', filters)"><Button variant="secondary">Summary</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Input v-model="dateFrom" type="date" class="!w-40" />
                <Input v-model="dateTo" type="date" class="!w-40" />
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
                <Select v-model="status" class="!w-36">
                    <option value="all">{{ t('common.allStatuses') }}</option>
                    <option value="completed">{{ filter('completed') }}</option>
                    <option value="voided">{{ filter('voided') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState v-if="!sales.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="sales.data" :paginated="sales" pagination-route="admin.reports.sales-register.index" :pagination-query="filterQuery" viewport-fit compact external-sort>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(sales.from, index) }}</span>
                </template>
                <template #cell-completed_at="{ row }">
                    {{ (row as SalesRegisterRow).completed_at ? new Date((row as SalesRegisterRow).completed_at!).toLocaleString() : '—' }}
                </template>
                <template #cell-sale_number="{ row }">
                    <span class="font-medium">{{ (row as SalesRegisterRow).sale_number }}</span>
                </template>
                <template #cell-store="{ row }">{{ (row as SalesRegisterRow).store?.store_name ?? '—' }}</template>
                <template #cell-user="{ row }">{{ (row as SalesRegisterRow).user?.name ?? '—' }}</template>
                <template #cell-customer="{ row }">
                    <span v-if="(row as SalesRegisterRow).customer">
                        {{ (row as SalesRegisterRow).customer!.first_name }}
                        {{ (row as SalesRegisterRow).customer!.last_name }}
                    </span>
                    <span v-else class="text-ink-muted">{{ filter('walkIn') }}</span>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as SalesRegisterRow).status === 'completed' ? 'success' : 'danger'">
                        {{ (row as SalesRegisterRow).status }}
                    </Badge>
                </template>
                <template #cell-grand_total="{ row }">
                    <span class="tabular-nums">{{ formatReportMoney((row as SalesRegisterRow).grand_total) }}</span>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
