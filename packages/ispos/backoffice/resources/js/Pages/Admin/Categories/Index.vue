<script setup lang="ts">
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
import { useModulePage } from '@/Composables/useModulePage';
import { usePermissions } from '@/Composables/usePermissions';
import { paginatedRowNumber, useAuditTableColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { CategoryRecord } from '@/types/category';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { can } = usePermissions();
const { t } = useI18n();
const page = useModulePage('categories');

const props = defineProps<{
    categories: Paginated<CategoryRecord>;
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
    'admin.categories.index',
    props.filters,
    ['search', 'company_id', 'store_id', 'status'],
    props.companies,
    props.stores,
);

const countLabel = useRecordCountLabel(() => props.categories.total, 'category');
const lineCol = useLineTableColumn();
const auditCols = useAuditTableColumns();

const columns = computed(() => [
    lineCol.value,
    { key: 'category_code', label: t('common.code') },
    { key: 'name', label: t('common.name') },
    { key: 'parent', label: t('common.parent') },
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

        <div class="index-page flex flex-col gap-2" :class="categories.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="can('categories.create')" #actions>
                    <Link :href="route('admin.categories.create')">
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
                v-if="!categories.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('categories.create') ? page.emptyAction : undefined"
                @action="can('categories.create') && router.visit(route('admin.categories.create'))"
            />

            <DataTable v-else :columns="columns" :rows="categories.data" :paginated="categories" pagination-route="admin.categories.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(categories.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <Link
                        :href="route('admin.categories.edit', (row as CategoryRecord).id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ (row as CategoryRecord).name }}
                    </Link>
                </template>
                <template #cell-parent="{ row }">
                    {{ (row as CategoryRecord).parent?.name ?? '—' }}
                </template>
                <template #cell-company="{ row }">
                    {{ (row as CategoryRecord).company?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as CategoryRecord).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as CategoryRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as CategoryRecord).creator" :at="(row as CategoryRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as CategoryRecord).updater" :at="(row as CategoryRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.categories.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
