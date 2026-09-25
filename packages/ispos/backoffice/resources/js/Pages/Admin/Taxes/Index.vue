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
import type { TaxRecord } from '@/types/tax';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('taxes');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([{ key: 'rate' }, { key: 'is_inclusive', col: 'inclusive' }]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    taxes: Paginated<TaxRecord>;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string }>;
    filters: { search: string; company_id: string; store_id: string; status: string };
}>();

const {
    search,
    companyId,
    storeId,
    status,
    showCompanyFilter,
    showStoreFilter,
    filteredStores,
    hasActiveFilters,
    clearFilters,
    filterQuery,
} = useCatalogIndexFilters(
    'admin.taxes.index',
    props.filters,
    ['search', 'company_id', 'store_id', 'status'],
    props.companies,
    props.stores,
);

const countLabel = useRecordCountLabel(() => props.taxes.total, 'tax');

const columns = computed(() => [
    lineCol.value,
    { key: 'tax_code', label: t('common.code') },
    { key: 'name', label: t('common.name') },
    ...extraCols.value,
    { key: 'company', label: t('common.company') },
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="taxes.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="can('taxes.create')" #actions>
                    <Link :href="route('admin.taxes.create')">
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
            <Select v-if="showStoreFilter" v-model="storeId" class="!w-44">
                <option value="">{{ t('common.allStores') }}</option>
                <option v-for="store in filteredStores" :key="store.id" :value="store.id">
                    {{ store.store_name }}
                </option>
            </Select>
            <StatusFilterSelect v-model="status" />
        </IndexToolbar>

        <EmptyState
            v-if="!taxes.data.length"
            :title="page.emptyTitle"
            :description="page.emptyDescription"
            :action-label="can('taxes.create') ? emptyAction('createTax') : undefined"
            @action="can('taxes.create') && router.visit(route('admin.taxes.create'))"
        />

        <DataTable v-else :columns="columns" :rows="taxes.data" :paginated="taxes" pagination-route="admin.taxes.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
            <template #cell-line="{ index }">
                <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(taxes.from, index) }}</span>
            </template>
            <template #cell-name="{ row }">
                <Link
                    :href="route('admin.taxes.edit', (row as TaxRecord).id)"
                    class="font-medium text-accent hover:underline"
                >
                    {{ (row as TaxRecord).name }}
                </Link>
            </template>
            <template #cell-rate="{ row }">
                {{ (row as TaxRecord).rate }}%
            </template>
            <template #cell-is_inclusive="{ row }">
                {{ (row as TaxRecord).is_inclusive ? 'Yes' : 'No' }}
            </template>
            <template #cell-company="{ row }">
                {{ (row as TaxRecord).company?.name ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="(row as TaxRecord).status === 'active' ? 'success' : 'neutral'">
                    {{ (row as TaxRecord).status }}
                </Badge>
            </template>
            <template #cell-creator="{ row }">
                <TableAuditStamp :user="(row as TaxRecord).creator" :at="(row as TaxRecord).created_at" />
            </template>
            <template #cell-last_modifier="{ row }">
                <TableAuditStamp :user="(row as TaxRecord).updater" :at="(row as TaxRecord).updated_at" />
            </template>
            <template #actions="{ row }">
                <Link :href="route('admin.taxes.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
            </template>
        </DataTable>
        </div>
    </AppLayout>
</template>
