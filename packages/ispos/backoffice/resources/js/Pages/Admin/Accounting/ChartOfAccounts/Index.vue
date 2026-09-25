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
import StatusFilterSelect from '@/Components/ui/StatusFilterSelect.vue';
import TableAuditStamp from '@/Components/ui/TableAuditStamp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import { paginatedRowNumber, useAuditTableColumns, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { ChartOfAccountRecord } from '@/types/chartOfAccount';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('chartOfAccounts');
const { t } = useI18n();
const auditCols = useAuditTableColumns();
const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'account_type', col: 'accountType' },
    { key: 'normal_balance', col: 'normal' },
]);

const props = defineProps<{
    accounts: Paginated<ChartOfAccountRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.chart-of-accounts.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.accounts.total, 'account');

const columns = computed(() => [
    lineCol.value,
    { key: 'account_code', label: t('common.code') },
    { key: 'account_name', label: t('common.name') },
    ...extraCols.value,
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="accounts.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('accounting.post')" #actions>
                    <Link :href="route('admin.chart-of-accounts.create')"><Button>{{ page.newButton }}</Button></Link>
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
                <StatusFilterSelect v-model="status" />
            </IndexToolbar>

            <DataTable v-if="accounts.data.length" :columns="columns" :rows="accounts.data" :paginated="accounts" pagination-route="admin.chart-of-accounts.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(accounts.from, index) }}</span>
                </template>
                <template #cell-account_type="{ row }">
                    <Badge variant="neutral">{{ row.account_type }}</Badge>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</Badge>
                </template>
                <template #cell-created_at="{ row }"><TableAuditStamp :user="row.creator" :at="row.created_at" /></template>
                <template #cell-updated_at="{ row }"><TableAuditStamp :user="row.updater" :at="row.updated_at" /></template>
                <template #actions="{ row }">
                    <Link v-if="can('accounting.post')" :href="route('admin.chart-of-accounts.edit', row.id)" class="text-sm font-medium text-accent hover:underline">{{ t('common.edit') }}</Link>
                    <button
                        v-if="can('accounting.post') && !row.is_system"
                        type="button"
                        class="text-sm font-medium text-danger hover:underline"
                        @click="router.delete(route('admin.chart-of-accounts.destroy', row.id))"
                    >
                        {{ t('common.delete') }}
                    </button>
                </template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
