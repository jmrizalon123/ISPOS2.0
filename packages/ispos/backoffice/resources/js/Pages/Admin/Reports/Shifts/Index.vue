<script setup lang="ts">
import { computed } from 'vue';
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
import { formatReportMoney, useReportFilters } from '@/Composables/useReportFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { Paginated } from '@/types';
import type { ShiftReportListRow } from '@/types/shiftReport';
import { Head, Link } from '@inertiajs/vue3';

const page = useModulePage('shiftReports');
const { t } = useI18n();
const { filter } = useLocale();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'opened_at', col: 'opened' },
    { key: 'register' },
    { key: 'cashier' },
    { key: 'transactions' },
    { key: 'gross_sales', col: 'gross' },
    { key: 'variance' },
]);

const props = defineProps<{
    shifts: Paginated<ShiftReportListRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string; search: string };
}>();

const { companyId, storeId, dateFrom, dateTo, status, search, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } =
    useReportFilters('admin.reports.shift-reports.index', props.filters, props.companies, ['search']);

const countLabel = useRecordCountLabel(() => props.shifts.total, 'shift');

const columns = computed(() => [
    lineCol.value,
    extraCols.value[0],
    { key: 'store', label: t('common.store') },
    ...extraCols.value.slice(1, 3),
    { key: 'status', label: t('common.status') },
    ...extraCols.value.slice(3),
    { key: 'actions', label: '' },
]);

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function statusVariant(statusValue: string): 'success' | 'warning' | 'neutral' {
    if (statusValue === 'open') {
        return 'warning';
    }
    if (statusValue === 'closed') {
        return 'success';
    }

    return 'neutral';
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="shifts.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template #actions>
                    <Link :href="route('admin.reports.sales-register.index', filters)"><Button variant="secondary">Sales register</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters || status !== 'all' || !!search" @clear="clearFilters">
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
                    <option value="all">{{ filter('allShifts') }}</option>
                    <option value="open">{{ filter('openShifts') }}</option>
                    <option value="closed">{{ filter('closedShifts') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState v-if="!shifts.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="shifts.data" :paginated="shifts" pagination-route="admin.reports.shift-reports.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(shifts.from, index) }}</span>
                </template>
                <template #cell-opened_at="{ row }">
                    {{ formatDateTime((row as ShiftReportListRow).opened_at) }}
                </template>
                <template #cell-store="{ row }">
                    {{ (row as ShiftReportListRow).store_name }}
                </template>
                <template #cell-register="{ row }">
                    {{ (row as ShiftReportListRow).register_name }}
                </template>
                <template #cell-cashier="{ row }">
                    {{ (row as ShiftReportListRow).cashier_name }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant((row as ShiftReportListRow).status)">
                        {{ (row as ShiftReportListRow).status }}
                    </Badge>
                </template>
                <template #cell-transactions="{ row }">
                    <span class="tabular-nums">{{ (row as ShiftReportListRow).transaction_count }}</span>
                </template>
                <template #cell-gross_sales="{ row }">
                    <span class="tabular-nums">{{ formatReportMoney((row as ShiftReportListRow).gross_sales) }}</span>
                </template>
                <template #cell-variance="{ row }">
                    <span
                        class="tabular-nums"
                        :class="{
                            'text-emerald-600 dark:text-emerald-400': Number((row as ShiftReportListRow).variance) === 0,
                            'text-amber-600 dark:text-amber-400': Number((row as ShiftReportListRow).variance) !== 0,
                        }"
                    >
                        {{
                            (row as ShiftReportListRow).variance !== null
                                ? formatReportMoney((row as ShiftReportListRow).variance!)
                                : '—'
                        }}
                    </span>
                </template>
                <template #cell-actions="{ row }">
                    <Link
                        :href="route('admin.reports.shift-reports.show', (row as ShiftReportListRow).id)"
                        class="text-sm font-medium text-accent hover:underline"
                    >
                        {{ t('pages.shiftReports.showHeader') }}
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
