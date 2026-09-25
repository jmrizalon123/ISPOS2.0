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
import type { MembershipPlanRecord } from '@/types/membershipPlan';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('membershipPlans');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'price' },
    { key: 'duration_days', col: 'duration' },
    { key: 'discount_percent', col: 'discount' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    membershipPlans: Paginated<MembershipPlanRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.membership-plans.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.membershipPlans.total, 'membershipPlan');

const columns = computed(() => [
    lineCol.value,
    { key: 'plan_code', label: t('common.code') },
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

        <div class="index-page flex flex-col gap-2" :class="membershipPlans.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('memberships.manage')" #actions>
                    <Link :href="route('admin.membership-plans.create')"><Button>{{ page.newButton }}</Button></Link>
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
                v-if="!membershipPlans.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('memberships.manage') ? emptyAction('createPlan') : undefined"
                @action="can('memberships.manage') && router.visit(route('admin.membership-plans.create'))"
            />

            <DataTable v-else :columns="columns" :rows="membershipPlans.data" :paginated="membershipPlans" pagination-route="admin.membership-plans.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(membershipPlans.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <Link :href="route('admin.membership-plans.edit', (row as MembershipPlanRecord).id)" class="font-medium text-accent hover:underline">
                        {{ (row as MembershipPlanRecord).name }}
                    </Link>
                </template>
                <template #cell-price="{ row }">
                    {{ (row as MembershipPlanRecord).price != null ? `₱${Number((row as MembershipPlanRecord).price).toFixed(2)}` : '—' }}
                </template>
                <template #cell-duration_days="{ row }">
                    {{ (row as MembershipPlanRecord).duration_days != null ? `${(row as MembershipPlanRecord).duration_days} days` : '—' }}
                </template>
                <template #cell-discount_percent="{ row }">{{ Number((row as MembershipPlanRecord).discount_percent).toFixed(1) }}%</template>
                <template #cell-company="{ row }">{{ (row as MembershipPlanRecord).company?.name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as MembershipPlanRecord).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as MembershipPlanRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as MembershipPlanRecord).creator" :at="(row as MembershipPlanRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as MembershipPlanRecord).updater" :at="(row as MembershipPlanRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.membership-plans.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
