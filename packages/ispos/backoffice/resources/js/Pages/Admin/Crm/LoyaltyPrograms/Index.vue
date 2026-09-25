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
import type { LoyaltyProgramRecord } from '@/types/loyaltyProgram';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('loyaltyPrograms');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'earn_rate', col: 'earnRate' },
    { key: 'is_default', col: 'default' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    loyaltyPrograms: Paginated<LoyaltyProgramRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.loyalty-programs.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.loyaltyPrograms.total, 'loyaltyProgram');

const columns = computed(() => [
    lineCol.value,
    { key: 'program_code', label: t('common.code') },
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

        <div class="index-page flex flex-col gap-2" :class="loyaltyPrograms.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('loyalty.manage')" #actions>
                    <Link :href="route('admin.loyalty-programs.create')"><Button>{{ page.newButton }}</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
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
                v-if="!loyaltyPrograms.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('loyalty.manage') ? emptyAction('createProgram') : undefined"
                @action="can('loyalty.manage') && router.visit(route('admin.loyalty-programs.create'))"
            />

            <DataTable v-else :columns="columns" :rows="loyaltyPrograms.data" :paginated="loyaltyPrograms" pagination-route="admin.loyalty-programs.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(loyaltyPrograms.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <Link :href="route('admin.loyalty-programs.edit', (row as LoyaltyProgramRecord).id)" class="font-medium text-accent hover:underline">
                        {{ (row as LoyaltyProgramRecord).name }}
                    </Link>
                </template>
                <template #cell-earn_rate="{ row }">{{ Number((row as LoyaltyProgramRecord).earn_rate).toFixed(2) }}×</template>
                <template #cell-is_default="{ row }">{{ (row as LoyaltyProgramRecord).is_default ? 'Yes' : '—' }}</template>
                <template #cell-company="{ row }">{{ (row as LoyaltyProgramRecord).company?.name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as LoyaltyProgramRecord).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as LoyaltyProgramRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as LoyaltyProgramRecord).creator" :at="(row as LoyaltyProgramRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as LoyaltyProgramRecord).updater" :at="(row as LoyaltyProgramRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.loyalty-programs.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
