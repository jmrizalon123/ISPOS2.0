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
import { formatReportMoney } from '@/Composables/useReportFilters';
import { useLineTableColumn } from '@/Composables/useTableColumns';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('balanceSheet');
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
    assets: StatementRow[];
    liabilities: StatementRow[];
    equity: StatementRow[];
    netIncome: string;
    totals: {
        assets: string;
        liabilities: string;
        equity: string;
        net_income: string;
        liabilities_and_equity: string;
    };
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { company_id: string; as_of_date: string };
}>();

const companyId = ref(props.filters.company_id);
const asOfDate = ref(props.filters.as_of_date);
const showCompanyFilter = computed(() => props.companies.length > 1);

watch([companyId, asOfDate], () => {
    router.get(
        route('admin.accounting.balance-sheet.index'),
        { company_id: companyId.value, as_of_date: asOfDate.value },
        { preserveState: true, replace: true },
    );
});

const hasData = computed(
    () => props.assets.length > 0 || props.liabilities.length > 0 || props.equity.length > 0 || Number(props.netIncome) !== 0,
);

const columns = computed(() => [
    lineCol.value,
    { key: 'account_code', label: t('common.code') },
    { key: 'account_name', label: 'Account' },
    { key: 'balance', label: 'Balance' },
]);

const balanced = computed(() => props.totals.assets === props.totals.liabilities_and_equity);
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
                    <Link :href="route('admin.accounting.profit-and-loss.index')"><Badge variant="neutral">Profit & loss →</Badge></Link>
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

            <section class="grid gap-4 sm:grid-cols-2">
                <Card :title="card('totalAssets')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.assets) }}</p>
                </Card>
                <Card :title="card('liabilitiesEquity')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.liabilities_and_equity) }}</p>
                    <p class="mt-1 text-xs" :class="balanced ? 'text-emerald-600' : 'text-amber-600'">
                        {{ balanced ? 'Balanced' : 'Includes unclosed net income' }}
                    </p>
                </Card>
            </section>

            <template v-if="hasData">
                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-muted">Assets</h3>
                    <DataTable v-if="assets.length" :columns="columns" :rows="assets" viewport-fit compact>
                        <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                        <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
                    </DataTable>
                    <p v-else class="text-sm text-ink-muted">No asset balances.</p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-muted">Liabilities</h3>
                    <DataTable v-if="liabilities.length" :columns="columns" :rows="liabilities" viewport-fit compact>
                        <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                        <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
                    </DataTable>
                    <p v-else class="text-sm text-ink-muted">No liability balances.</p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-muted">Equity</h3>
                    <DataTable v-if="equity.length" :columns="columns" :rows="equity" viewport-fit compact>
                        <template #cell-line="{ index }"><span class="tabular-nums text-ink-muted">{{ index + 1 }}</span></template>
                        <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
                    </DataTable>
                    <div v-if="Number(netIncome) !== 0" class="mt-2 flex justify-between rounded-lg border border-border px-3 py-2 text-sm">
                        <span>Net income (unclosed)</span>
                        <span class="font-medium tabular-nums">{{ formatReportMoney(netIncome) }}</span>
                    </div>
                    <p v-if="!equity.length && Number(netIncome) === 0" class="text-sm text-ink-muted">No equity balances.</p>
                </div>
            </template>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
