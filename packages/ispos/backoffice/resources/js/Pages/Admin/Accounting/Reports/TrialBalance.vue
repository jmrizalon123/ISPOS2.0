<script setup lang="ts">
import { computed } from 'vue';
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

const page = useModulePage('trialBalance');
const { t, card } = useLocale();

interface TrialBalanceRow {
    account_id: string;
    account_code: string;
    account_name: string;
    account_type: string;
    debit_total: string;
    credit_total: string;
    balance: string;
}

const lineCol = useLineTableColumn();

const props = defineProps<{
    rows: TrialBalanceRow[];
    totals: { debits: string; credits: string };
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string };
}>();

const { companyId, dateFrom, dateTo, showCompanyFilter, hasActiveFilters, clearFilters } = useReportFilters(
    'admin.accounting.trial-balance.index',
    props.filters,
    props.companies,
);

const columns = computed(() => [
    lineCol.value,
    { key: 'account_code', label: t('common.code') },
    { key: 'account_name', label: 'Account' },
    { key: 'account_type', label: 'Type' },
    { key: 'debit_total', label: 'Debits' },
    { key: 'credit_total', label: 'Credits' },
    { key: 'balance', label: 'Balance' },
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
                    <Link :href="route('admin.journal-entries.index')"><Badge variant="neutral">Journal entries →</Badge></Link>
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

            <section class="grid gap-4 sm:grid-cols-2">
                <Card :title="card('totalDebits')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.debits) }}</p>
                </Card>
                <Card :title="card('totalCredits')">
                    <p class="text-2xl font-semibold text-ink">{{ formatReportMoney(totals.credits) }}</p>
                </Card>
            </section>

            <DataTable v-if="rows.length" :columns="columns" :rows="rows" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ index + 1 }}</span>
                </template>
                <template #cell-debit_total="{ row }">{{ formatReportMoney(row.debit_total) }}</template>
                <template #cell-credit_total="{ row }">{{ formatReportMoney(row.credit_total) }}</template>
                <template #cell-balance="{ row }">{{ formatReportMoney(row.balance) }}</template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
