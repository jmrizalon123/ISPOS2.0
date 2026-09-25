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
import { useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { ProductSalesRow } from '@/types/salesReport';
import { Head, Link } from '@inertiajs/vue3';

const page = useModulePage('productSales');
const { t } = useI18n();
const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'sku' },
    { key: 'name', col: 'product' },
    { key: 'qty_sold', col: 'qtySold' },
    { key: 'revenue' },
]);

const props = defineProps<{
    products: ProductSalesRow[];
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string };
}>();

const { companyId, storeId, dateFrom, dateTo, showCompanyFilter, hasActiveFilters, clearFilters } = useReportFilters(
    'admin.reports.product-sales.index',
    props.filters,
    props.companies,
);

const columns = computed(() => [lineCol.value, ...extraCols.value]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="flex flex-col gap-2">
            <IndexPageHeader
                :back-href="route('admin.reports.sales-summary.index', filters)"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta><Badge variant="neutral">{{ products.length }} {{ t('entities.products') }}</Badge></template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
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
            </IndexToolbar>

            <EmptyState v-if="!products.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="products" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ index + 1 }}</span>
                </template>
                <template #cell-name="{ row }">
                    <span class="font-medium">{{ (row as ProductSalesRow).name }}</span>
                </template>
                <template #cell-qty_sold="{ row }">{{ parseFloat((row as ProductSalesRow).qty_sold).toLocaleString() }}</template>
                <template #cell-revenue="{ row }">{{ formatReportMoney((row as ProductSalesRow).revenue) }}</template>
            </DataTable>
        </div>
    </AppLayout>
</template>
