<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { PurchaseReturnRecord } from '@/types/purchaseReturn';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { can } = usePermissions();
const page = useModulePage('purchaseReturns');
const { t } = useI18n();
const { filter, emptyAction } = useLocale();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'return_number', col: 'returnNumber' },
    { key: 'supplier' },
    { key: 'reason' },
]);

const props = defineProps<{
    purchaseReturns: Paginated<PurchaseReturnRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; status: string };
}>();

const storeId = ref(props.filters.store_id);
const status = ref(props.filters.status);
const filterQuery = computed(() => ({
    store_id: storeId.value || undefined,
    status: status.value || undefined,
}));
const hasActiveFilters = computed(() => !!storeId.value || !!status.value);
const countLabel = useRecordCountLabel(() => props.purchaseReturns.total, 'return');

const columns = computed(() => [
    lineCol.value,
    ...extraCols.value.slice(0, 2),
    { key: 'store', label: t('common.store') },
    extraCols.value[2],
    { key: 'status', label: t('common.status') },
]);

function applyFilters() {
    router.get(
        route('admin.purchase-returns.index'),
        { store_id: storeId.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

watch([storeId, status], applyFilters);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="purchaseReturns.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('purchasing.create')" #actions>
                    <Link :href="route('admin.purchase-returns.create')"><Button>{{ page.newButton }}</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="() => { storeId = ''; status = ''; applyFilters(); }">
                <Select v-model="storeId" class="!w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.store_name }}</option>
                </Select>
                <Select v-model="status" class="!w-44">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="draft">{{ filter('draft') }}</option>
                    <option value="posted">{{ filter('posted') }}</option>
                    <option value="cancelled">{{ filter('cancelled') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState
                v-if="!purchaseReturns.data.length"
                :title="page.emptyTitle"
                :action-label="can('purchasing.create') ? emptyAction('createReturn') : undefined"
                @action="can('purchasing.create') && router.visit(route('admin.purchase-returns.create'))"
            />

            <DataTable v-else :columns="columns" :rows="purchaseReturns.data" :paginated="purchaseReturns" pagination-route="admin.purchase-returns.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(purchaseReturns.from, index) }}</span>
                </template>
                <template #cell-return_number="{ row }">
                    <Link :href="route('admin.purchase-returns.edit', (row as PurchaseReturnRecord).id)" class="font-medium text-accent hover:underline">
                        {{ (row as PurchaseReturnRecord).return_number }}
                    </Link>
                </template>
                <template #cell-supplier="{ row }">{{ (row as PurchaseReturnRecord).supplier?.name ?? '—' }}</template>
                <template #cell-store="{ row }">{{ (row as PurchaseReturnRecord).store?.store_name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as PurchaseReturnRecord).status === 'posted' ? 'success' : 'neutral'">
                        {{ (row as PurchaseReturnRecord).status }}
                    </Badge>
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.purchase-returns.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.view') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
