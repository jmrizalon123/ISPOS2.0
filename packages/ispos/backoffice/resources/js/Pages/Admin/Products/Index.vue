<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Dropdown from '@/Components/ui/Dropdown.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
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
import type { ProductRecord, StockStatus } from '@/types/product';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { can } = usePermissions();
const page = useModulePage('products');
const { t } = useI18n();
const { filter, emptyAction, stockStatus: stockStatusLabel } = useLocale();
const auditCols = useAuditTableColumns();
const extraCols = useColumns([
    { key: 'image', class: 'w-14' },
    { key: 'sku' },
    { key: 'sales_plan', col: 'salesPlan' },
    { key: 'category' },
    { key: 'brand' },
    { key: 'qty' },
    { key: 'stock_status', col: 'stock' },
    { key: 'base_price', col: 'basePrice' },
]);
const lineCol = useLineTableColumn();

const props = defineProps<{
    products: Paginated<ProductRecord>;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string }>;
    categories: Array<{ id: string; name: string; category_code: string; company_id: string }>;
    brands: Array<{ id: string; name: string; brand_code: string; company_id: string }>;
    filters: {
        search: string;
        company_id: string;
        store_id: string;
        category_id: string;
        brand_id: string;
    };
}>();

const {
    search,
    companyId,
    storeId,
    categoryId,
    brandId,
    showCompanyFilter,
    showStoreFilter,
    filteredStores,
    hasActiveFilters,
    clearFilters,
    filterQuery,
} = useCatalogIndexFilters(
    'admin.products.index',
    props.filters,
    ['search', 'company_id', 'store_id', 'category_id', 'brand_id'],
    props.companies,
    props.stores,
);

const filteredCategories = computed(() =>
    props.categories.filter((category) => !companyId.value || category.company_id === companyId.value),
);

const filteredBrands = computed(() =>
    props.brands.filter((brand) => !companyId.value || brand.company_id === companyId.value),
);

const columns = computed(() => [
    lineCol.value,
    extraCols.value[0],
    { key: 'name', label: t('common.name') },
    ...extraCols.value.slice(1),
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);

function stockBadgeVariant(status: StockStatus): 'success' | 'warning' | 'danger' | 'neutral' {
    if (status === 'low') return 'warning';
    if (status === 'out') return 'danger';
    if (status === 'ok') return 'success';
    return 'neutral';
}

function stockLabel(status: StockStatus): string {
    if (status === 'low') return stockStatusLabel('low');
    if (status === 'out') return stockStatusLabel('out');
    if (status === 'ok') return stockStatusLabel('ok');
    return '—';
}

const productCountLabel = useRecordCountLabel(() => props.products.total, 'product');
const createMenuOpen = ref(false);

function productImageUrl(product: ProductRecord): string | null {
    return product.image?.trim() || null;
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="products.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ productCountLabel }}</Badge>
                    <Badge variant="accent">Retail & restaurant</Badge>
                </template>
                <template v-if="can('products.create')" #actions>
                    <Dropdown v-model:open="createMenuOpen" align="right" width="52" content-classes="py-1 bg-surface">
                        <template #trigger="{ open }">
                            <Button type="button" class="index-create-menu__trigger">
                                <span>{{ page.newButton }}</span>
                                <NavIcon name="chevron-down" class="index-create-menu__chevron" :class="open ? 'index-create-menu__chevron--open' : undefined" />
                            </Button>
                        </template>
                        <template #content>
                            <Link
                                :href="route('admin.products.create')"
                                class="index-create-menu__option"
                                @click="createMenuOpen = false"
                            >
                                {{ t('forms.actions.singleCreate') }}
                            </Link>
                            <Link
                                :href="route('admin.products.bulk-create')"
                                class="index-create-menu__option"
                                @click="createMenuOpen = false"
                            >
                                {{ t('forms.actions.bulkCreate') }}
                            </Link>
                        </template>
                    </Dropdown>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-full sm:!w-64 xl:!w-72" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-full sm:!w-44 xl:!w-48">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-if="showStoreFilter" v-model="storeId" class="!w-full sm:!w-44 xl:!w-48">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in filteredStores" :key="store.id" :value="store.id">
                        {{ store.store_name }}
                    </option>
                </Select>
                <Select v-model="categoryId" class="!w-full sm:!w-44 xl:!w-48">
                    <option value="">{{ filter('allCategories') }}</option>
                    <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </Select>
                <Select v-model="brandId" class="!w-full sm:!w-44 xl:!w-48">
                    <option value="">{{ filter('allBrands') }}</option>
                    <option v-for="brand in filteredBrands" :key="brand.id" :value="brand.id">
                        {{ brand.name }}
                    </option>
                </Select>
            </IndexToolbar>

        <EmptyState
            v-if="!products.data.length"
            :title="page.emptyTitle"
            :description="page.emptyDescription"
            :action-label="can('products.create') ? emptyAction('createProduct') : undefined"
            @action="can('products.create') && router.visit(route('admin.products.create'))"
        />

        <DataTable v-else :columns="columns" :rows="products.data" :paginated="products" pagination-route="admin.products.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
            <template #cell-line="{ index }">
                <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(products.from, index) }}</span>
            </template>
            <template #cell-image="{ row }">
                <div
                    class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md border border-line bg-surface-muted"
                >
                    <img
                        v-if="productImageUrl(row as ProductRecord)"
                        :src="productImageUrl(row as ProductRecord)!"
                        :alt="(row as ProductRecord).name"
                        class="h-full w-full object-cover"
                    />
                    <svg
                        v-else
                        class="h-4 w-4 text-ink-muted/50"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"
                        />
                    </svg>
                </div>
            </template>
            <template #cell-name="{ row }">
                <Link
                    :href="route('admin.products.edit', (row as ProductRecord).id)"
                    class="font-medium text-accent hover:underline"
                >
                    {{ (row as ProductRecord).name }}
                </Link>
            </template>
            <template #cell-sales_plan="{ row }">
                {{ (row as ProductRecord).sales_plan?.name ?? '—' }}
            </template>
            <template #cell-category="{ row }">
                {{ (row as ProductRecord).category?.name ?? '—' }}
            </template>
            <template #cell-brand="{ row }">
                {{ (row as ProductRecord).brand?.name ?? '—' }}
            </template>
            <template #cell-qty="{ row }">
                <span v-if="(row as ProductRecord).track_inventory">
                    {{ (row as ProductRecord).track_inventory ? ((row as ProductRecord).total_qty ?? (row as ProductRecord).qty) : '—' }}
                </span>
                <span v-else class="text-ink-muted">—</span>
            </template>
            <template #cell-stock_status="{ row }">
                <Badge
                    v-if="(row as ProductRecord).track_inventory"
                    :variant="stockBadgeVariant((row as ProductRecord).stock_status ?? 'not_tracked')"
                >
                    {{ stockLabel((row as ProductRecord).stock_status ?? 'not_tracked') }}
                </Badge>
                <span v-else class="text-ink-muted">—</span>
            </template>
            <template #cell-base_price="{ row }">
                {{ (row as ProductRecord).base_price }}
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="(row as ProductRecord).status === 'active' ? 'success' : 'neutral'">
                    {{ (row as ProductRecord).status }}
                </Badge>
            </template>
            <template #cell-creator="{ row }">
                <TableAuditStamp :user="(row as ProductRecord).creator" :at="(row as ProductRecord).created_at" />
            </template>
            <template #cell-last_modifier="{ row }">
                <TableAuditStamp :user="(row as ProductRecord).updater" :at="(row as ProductRecord).updated_at" />
            </template>
            <template #actions="{ row }">
                <Link :href="route('admin.products.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
            </template>
        </DataTable>
        </div>
    </AppLayout>
</template>
