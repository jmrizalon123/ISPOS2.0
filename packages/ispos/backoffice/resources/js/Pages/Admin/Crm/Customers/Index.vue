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
import type { CustomerRecord } from '@/types/customer';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('customers');
const { t } = useI18n();
const { emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'email' },
    { key: 'phone' },
    { key: 'loyalty_points', col: 'points' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    customers: Paginated<CustomerRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.customers.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.customers.total, 'customer');

const columns = computed(() => [
    lineCol.value,
    { key: 'customer_code', label: t('common.code') },
    { key: 'name', label: t('common.name') },
    ...extraCols.value,
    { key: 'company', label: t('common.company') },
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);

function customerName(customer: CustomerRecord): string {
    return [customer.first_name, customer.last_name].filter(Boolean).join(' ');
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="customers.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('customers.create')" #actions>
                    <Link :href="route('admin.customers.create')"><Button>{{ page.newButton }}</Button></Link>
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
                v-if="!customers.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('customers.create') ? emptyAction('createCustomer') : undefined"
                @action="can('customers.create') && router.visit(route('admin.customers.create'))"
            />

            <DataTable v-else :columns="columns" :rows="customers.data" :paginated="customers" pagination-route="admin.customers.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(customers.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <Link :href="route('admin.customers.edit', (row as CustomerRecord).id)" class="font-medium text-accent hover:underline">
                        {{ customerName(row as CustomerRecord) }}
                    </Link>
                </template>
                <template #cell-email="{ row }">{{ (row as CustomerRecord).email ?? '—' }}</template>
                <template #cell-phone="{ row }">{{ (row as CustomerRecord).phone ?? (row as CustomerRecord).mobile ?? '—' }}</template>
                <template #cell-loyalty_points="{ row }">
                    <span class="tabular-nums">{{ Number((row as CustomerRecord).loyalty_points).toLocaleString() }}</span>
                </template>
                <template #cell-company="{ row }">{{ (row as CustomerRecord).company?.name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as CustomerRecord).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as CustomerRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as CustomerRecord).creator" :at="(row as CustomerRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as CustomerRecord).updater" :at="(row as CustomerRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.customers.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
