<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Badge from '@/Components/ui/Badge.vue';
import TableAuditStamp from '@/Components/ui/TableAuditStamp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useAuditTableColumns, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import { confirmDelete } from '@/Composables/useConfirm';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import type { CompanyRecord } from '@/types/company';
import type { Paginated } from '@/types';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { can } = usePermissions();
const page = useModulePage('companies');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'display_name', col: 'displayName' },
    { key: 'name', col: 'legalName' },
    { key: 'industry' },
    { key: 'base_currency', col: 'currency' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    companies: Paginated<CompanyRecord>;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const filterQuery = computed(() => ({ search: search.value || undefined }));
const hasActiveFilters = computed(() => !!search.value);
const countLabel = useRecordCountLabel(() => props.companies.total, 'company');

watch(search, (value) => {
    router.get(route('admin.companies.index'), { search: value || undefined }, { preserveState: true, replace: true });
});

function clearFilters() {
    search.value = '';
}

const columns = computed(() => [
    lineCol.value,
    { key: 'company_code', label: t('common.code') },
    ...extraCols.value,
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);

function displayName(row: CompanyRecord): string {
    return row.display_name || row.name;
}

async function destroy(id: string, name: string) {
    if (!(await confirmDelete(`company “${name}”`))) {
        return;
    }

    router.delete(route('admin.companies.destroy', id));
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="companies.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="can('companies.create')" #actions>
                    <Link :href="route('admin.companies.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
            </IndexToolbar>

            <EmptyState
                v-if="!companies.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('companies.create') ? emptyAction('createCompany') : undefined"
                @action="can('companies.create') && router.visit(route('admin.companies.create'))"
            />

            <DataTable v-else :columns="columns" :rows="companies.data" :paginated="companies" pagination-route="admin.companies.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(companies.from, index) }}</span>
                </template>
                <template #cell-display_name="{ row }">
                    <Link
                        :href="route('admin.companies.edit', (row as CompanyRecord).id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ displayName(row as CompanyRecord) }}
                    </Link>
                </template>
                <template #cell-industry="{ row }">
                    {{ (row as CompanyRecord).industry || '—' }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as CompanyRecord).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as CompanyRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as CompanyRecord).creator" :at="(row as CompanyRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as CompanyRecord).updater" :at="(row as CompanyRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <div class="flex justify-end gap-2">
                        <Link
                            :href="route('admin.companies.edit', row.id)"
                            class="text-sm text-accent hover:underline"
                        >
                            {{ t('common.edit') }}
                        </Link>
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:underline"
                            @click="destroy(String(row.id), displayName(row as CompanyRecord))"
                        >
                            {{ t('common.delete') }}
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
