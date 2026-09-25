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
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { JournalEntryRecord } from '@/types/journalEntry';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('journalEntries');
const { t } = useI18n();
const { filter } = useLocale();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'entry_date', col: 'date' },
    { key: 'entry_number', col: 'entryNumber' },
    { key: 'description' },
    { key: 'source_type', col: 'source' },
]);

const props = defineProps<{
    entries: Paginated<JournalEntryRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.journal-entries.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.entries.total, 'entry');

const columns = computed(() => [
    lineCol.value,
    ...extraCols.value.slice(0, 3),
    { key: 'status', label: t('common.status') },
    extraCols.value[3],
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="entries.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('accounting.post')" #actions>
                    <Link :href="route('admin.journal-entries.create')"><Button>{{ page.newButton }}</Button></Link>
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
                <Select v-model="status" class="!w-36">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="draft">{{ filter('draft') }}</option>
                    <option value="posted">{{ filter('posted') }}</option>
                    <option value="voided">{{ filter('voided') }}</option>
                </Select>
            </IndexToolbar>

            <DataTable v-if="entries.data.length" :columns="columns" :rows="entries.data" :paginated="entries" pagination-route="admin.journal-entries.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(entries.from, index) }}</span>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.status === 'posted' ? 'success' : row.status === 'draft' ? 'neutral' : 'danger'">{{ row.status }}</Badge>
                </template>
                <template #cell-source_type="{ row }">{{ row.source_type ? 'System' : 'Manual' }}</template>
                <template #actions="{ row }">
                    <Link :href="route('admin.journal-entries.edit', row.id)" class="text-sm font-medium text-accent hover:underline">Open</Link>
                </template>
            </DataTable>
            <EmptyState v-else :title="page.emptyTitle" :description="page.emptyDescription" />
        </div>
    </AppLayout>
</template>
