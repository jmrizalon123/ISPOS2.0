<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatReportMoney } from '@/Composables/useReportFilters';
import { useLineTableColumn } from '@/Composables/useTableColumns';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('apAging');
const { t } = useI18n();

interface ApAgingRow {
    bill_id: string;
    bill_number: string;
    supplier_id: string;
    supplier_name: string | null;
    bill_date: string;
    due_date: string;
    days_past_due: number;
    bucket: string;
    amount_due: string;
    amount_paid: string;
    balance_due: string;
}

const lineCol = useLineTableColumn();

const props = defineProps<{
    rows: ApAgingRow[];
    totals: {
        current: string;
        bucket_1_30: string;
        bucket_31_60: string;
        bucket_61_90: string;
        over_90: string;
        total: string;
    };
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { company_id: string; as_of_date: string };
}>();

const companyId = ref(props.filters.company_id);
const asOfDate = ref(props.filters.as_of_date);
const showCompanyFilter = computed(() => props.companies.length > 1);

watch([companyId, asOfDate], () => {
    router.get(
        route('admin.accounting.ap-aging.index'),
        { company_id: companyId.value, as_of_date: asOfDate.value },
        { preserveState: true, replace: true },
    );
});

const columns = computed(() => [
    lineCol.value,
    { key: 'supplier_name', label: 'Supplier' },
    { key: 'bill_number', label: 'Bill #' },
    { key: 'due_date', label: 'Due date' },
    { key: 'days_past_due', label: 'Days past due' },
    { key: 'bucket', label: 'Bucket' },
    { key: 'balance_due', label: 'Balance' },
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2">
            <IndexPageHeader
                :back-href="route('admin.vendor-bills.index')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #actions>
                    <Link :href="route('admin.vendor-bills.index')"><Badge variant="neutral">Vendor bills →</Badge></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar>
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Input v-model="asOfDate" type="date" class="!w-40" />
            </IndexToolbar>

            <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                <Card class="p-3"><p class="text-xs text-ink-muted">Current</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.current) }}</p></Card>
                <Card class="p-3"><p class="text-xs text-ink-muted">1–30 days</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.bucket_1_30) }}</p></Card>
                <Card class="p-3"><p class="text-xs text-ink-muted">31–60 days</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.bucket_31_60) }}</p></Card>
                <Card class="p-3"><p class="text-xs text-ink-muted">61–90 days</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.bucket_61_90) }}</p></Card>
                <Card class="p-3"><p class="text-xs text-ink-muted">Over 90</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.over_90) }}</p></Card>
                <Card class="p-3"><p class="text-xs text-ink-muted">Total AP</p><p class="text-lg font-semibold">{{ formatReportMoney(totals.total) }}</p></Card>
            </div>

            <DataTable v-if="rows.length" :columns="columns" :rows="rows" viewport-fit compact>
                <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                <template #cell-balance_due="{ row }">{{ formatReportMoney(row.balance_due) }}</template>
                <template #cell-bucket="{ row }"><Badge variant="neutral">{{ row.bucket }}</Badge></template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
