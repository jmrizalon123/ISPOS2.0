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
import TableAuditStamp from '@/Components/ui/TableAuditStamp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useAuditTableColumns, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { PromotionRecord } from '@/types/promotion';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const { can } = usePermissions();
const page = useModulePage('promotions');
const { t } = useI18n();
const { filter, emptyAction } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'promo_type', col: 'promoType' },
    { key: 'discount_value', col: 'discount' },
    { key: 'applies_to', col: 'appliesTo' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    promotions: Paginated<PromotionRecord>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.promotions.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.promotions.total, 'promotion');

const statusVariant: Record<string, 'neutral' | 'success' | 'warning' | 'danger'> = {
    draft: 'neutral',
    active: 'success',
    expired: 'warning',
    cancelled: 'danger',
};

const columns = computed(() => [
    lineCol.value,
    { key: 'promo_code', label: t('common.code') },
    { key: 'name', label: t('common.name') },
    ...extraCols.value,
    { key: 'company', label: t('common.company') },
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);

function formatDiscount(promotion: PromotionRecord): string {
    if (promotion.promo_type === 'percent_off') {
        return `${Number(promotion.discount_value).toFixed(1)}%`;
    }

    return `₱${Number(promotion.discount_value).toFixed(2)}`;
}

function activatePromotion(id: string) {
    router.post(route('admin.promotions.activate', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="promotions.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('promotions.create')" #actions>
                    <Link :href="route('admin.promotions.create')"><Button>{{ page.newButton }}</Button></Link>
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
                <Select v-model="status" class="!w-44">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="draft">{{ filter('draft') }}</option>
                    <option value="active">{{ t('common.active') }}</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">{{ filter('cancelled') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState
                v-if="!promotions.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('promotions.create') ? emptyAction('createPromotion') : undefined"
                @action="can('promotions.create') && router.visit(route('admin.promotions.create'))"
            />

            <DataTable v-else :columns="columns" :rows="promotions.data" :paginated="promotions" pagination-route="admin.promotions.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(promotions.from, index) }}</span>
                </template>
                <template #cell-promo_code="{ row }">
                    <Link :href="route('admin.promotions.edit', (row as PromotionRecord).id)" class="font-medium text-accent hover:underline">
                        {{ (row as PromotionRecord).promo_code }}
                    </Link>
                </template>
                <template #cell-name="{ row }">
                    {{ (row as PromotionRecord).name }}
                </template>
                <template #cell-promo_type="{ row }">
                    {{ (row as PromotionRecord).promo_type === 'percent_off' ? 'Percent off' : 'Fixed amount' }}
                </template>
                <template #cell-discount_value="{ row }">{{ formatDiscount(row as PromotionRecord) }}</template>
                <template #cell-applies_to="{ row }">{{ (row as PromotionRecord).applies_to }}</template>
                <template #cell-company="{ row }">{{ (row as PromotionRecord).company?.name ?? '—' }}</template>
                <template #cell-status="{ row }">
                    <Badge :variant="statusVariant[(row as PromotionRecord).status] ?? 'neutral'">
                        {{ (row as PromotionRecord).status }}
                    </Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="(row as PromotionRecord).creator" :at="(row as PromotionRecord).created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="(row as PromotionRecord).updater" :at="(row as PromotionRecord).updated_at" />
                </template>
                <template #actions="{ row }">
                    <div class="flex gap-2">
                        <Link :href="route('admin.promotions.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                        <button
                            v-if="row.status === 'draft' && can('promotions.active')"
                            type="button"
                            class="text-sm text-accent hover:underline"
                            @click="activatePromotion(row.id)"
                        >
                            Activate
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
