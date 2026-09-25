<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/Composables/useLocale';
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
import { formatReportMoney, useReportFilters } from '@/Composables/useReportFilters';
import { useLineTableColumn } from '@/Composables/useTableColumns';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('profitAndLoss');
const { t, card } = useLocale();

interface StatementRow {
    account_id: string;
    account_code: string;
    account_name: string;
    account_type: string;
    balance: string;
}

const lineCol = useLineTableColumn();

const props = defineProps<{
    revenue: StatementRow[];
    expenses: StatementRow[];
    totals: { revenue: string; expenses: string; net_income: string };
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string };
}>();

const { companyId, dateFrom, dateTo, showCompanyFilter, hasActiveFilters, clearFilters } = useReportFilters(
    'admin.accounting.profit-and-loss.index',
    props.filters,
    props.companies,
);

const hasData = computed(() => props.revenue.length > 0 || props.expenses.length > 0);

const columns = computed(() => [
    lineCol.value,
    { key: 'account_code', label: t('common.code') },
    { key: 'account_name', label: 'Account' },
    { key: 'balance', label: 'Amount' },
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2">
            <IndexPageHeader
                :back-href="route('admin.journal-entries.index')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #actions>
                    <Link :href="route('admin.accounting.balance-sheet.index')"><Badge variant="neutral">Balance sheet →</Badge></Link>
                </template>
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
            </IndexToolbar>

            <section class="grid gap-4 sm:grid-cols-3">
                <Card :title="card('revenue')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.revenue) }}</p>
                </Card>
                <Card :title="card('expenses')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.expenses) }}</p>
                </Card>
                <Card :title="card('netIncome')">
                    <p class="text-2xl font-semibold" :class="Number(totals.net_income) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ formatReportMoney(totals.net_income) }}
                    </p>
                </Card>
            </section>

            <template v-if="hasData">
                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-muted">Revenue</h3>
                    <DataTable v-if="revenue.length" :columns="columns" :rows="revenue" viewport-fit compact>
                        <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                        <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
                    </DataTable>
                    <p v-else class="text-sm text-ink-muted">No revenue activity in this period.</p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-muted">Expenses</h3>
                    <DataTable v-if="expenses.length" :columns="columns" :rows="expenses" viewport-fit compact>
                        <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                        <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
                    </DataTable>
                    <p v-else class="text-sm text-ink-muted">No expense activity in this period.</p>
                </div>
            </template>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
