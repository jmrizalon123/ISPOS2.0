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
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useAuditTableColumns, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { StoreRecord } from '@/types/store';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('stores');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'store_name', col: 'storeName' },
    { key: 'sales_plan', col: 'salesPlan' },
    { key: 'city' },
    { key: 'phone' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    stores: Paginated<StoreRecord>;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.stores.index',
    props.filters,
    ['search', 'company_id', 'status'],
    props.companies,
);

const countLabel = useRecordCountLabel(() => props.stores.total, 'store');

const columns = computed(() => [
    lineCol.value,
    { key: 'store_code', label: t('common.code') },
    ...extraCols.value,
    { key: 'company', label: t('common.company') },
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);

function companyLabel(company: StoreRecord['company']): string {
    if (!company) {
        return '—';
    }

    return company.display_name || company.name;
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="stores.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="can('stores.create')" #actions>
                    <Link :href="route('admin.stores.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
            <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
            <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                <option value="">{{ t('common.allCompanies') }}</option>
                <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.display_name || company.name }}
                </option>
            </Select>
            <StatusFilterSelect v-model="status" />
        </IndexToolbar>

        <EmptyState
            v-if="!stores.data.length"
            :title="page.emptyTitle"
            :description="page.emptyDescription"
            :action-label="can('stores.create') ? emptyAction('createStore') : undefined"
            @action="can('stores.create') && router.visit(route('admin.stores.create'))"
        />

        <DataTable v-else :columns="columns" :rows="stores.data" :paginated="stores" pagination-route="admin.stores.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
            <template #cell-line="{ index }">
                <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(stores.from, index) }}</span>
            </template>
            <template #cell-store_name="{ row }">
                <Link
                    :href="route('admin.stores.edit', (row as StoreRecord).id)"
                    class="font-medium text-accent hover:underline"
                >
                    {{ (row as StoreRecord).store_name }}
                </Link>
            </template>
            <template #cell-sales_plan="{ row }">
                {{ (row as StoreRecord).sales_plan?.name ?? '—' }}
            </template>
            <template #cell-company="{ row }">
                {{ companyLabel((row as StoreRecord).company) }}
            </template>
            <template #cell-city="{ row }">
                {{ (row as StoreRecord).city || '—' }}
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="(row as StoreRecord).status === 'active' ? 'success' : 'neutral'">
                    {{ (row as StoreRecord).status }}
                </Badge>
            </template>
            <template #cell-creator="{ row }">
                <TableAuditStamp :user="(row as StoreRecord).creator" :at="(row as StoreRecord).created_at" />
            </template>
            <template #cell-last_modifier="{ row }">
                <TableAuditStamp :user="(row as StoreRecord).updater" :at="(row as StoreRecord).updated_at" />
            </template>
            <template #actions="{ row }">
                <Link :href="route('admin.stores.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
            </template>
        </DataTable>
        </div>
    </AppLayout>
</template>
