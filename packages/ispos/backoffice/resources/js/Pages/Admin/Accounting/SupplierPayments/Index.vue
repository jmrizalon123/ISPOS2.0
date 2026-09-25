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
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import { formatReportMoney } from '@/Composables/useReportFilters';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { SupplierPaymentRecord } from '@/types/supplierPayment';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('supplierPayments');
const { t } = useI18n();
const { emptyAction } = useLocale();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'payment_date', col: 'date' },
    { key: 'payment_number', col: 'paymentNumber' },
    { key: 'supplier' },
    { key: 'payment_method', col: 'method' },
    { key: 'amount' },
]);

const props = defineProps<{
    payments: Paginated<SupplierPaymentRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.supplier-payments.index',
    { ...props.filters, store_id: '', status: '' },
    ['search', 'company_id'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.payments.total, 'payment');

const columns = computed(() => [lineCol.value, ...extraCols.value]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="payments.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('ap.pay')" #actions>
                    <Link :href="route('admin.supplier-payments.create')"><Button>{{ emptyAction('recordPayment') }}</Button></Link>
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
            </IndexToolbar>

            <DataTable v-if="payments.data.length" :columns="columns" :rows="payments.data" :paginated="payments" pagination-route="admin.supplier-payments.index" :pagination-query="filterQuery" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(payments.from, index) }}</span>
                </template>
                <template #cell-supplier="{ row }">{{ row.supplier?.name ?? '—' }}</template>
                <template #cell-amount="{ row }">{{ formatReportMoney(String(row.amount)) }}</template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
