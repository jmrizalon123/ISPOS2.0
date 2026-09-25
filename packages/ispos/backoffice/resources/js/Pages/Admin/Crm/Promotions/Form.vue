<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormField from '@/Components/forms/FormField.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Dropdown from '@/Components/ui/Dropdown.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import Input from '@/Components/ui/Input.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import Select from '@/Components/ui/Select.vue';
import TablePagination from '@/Components/ui/TablePagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { defaultPromotionForm, type PromotionFormData, type PromotionRecord } from '@/types/promotion';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type PromotionStatus = 'draft' | 'active' | 'expired' | 'cancelled';

type PromotionProductOption = {
    id: string;
    name: string;
    sku: string;
    cost?: string | number | null;
    qty?: string | number | null;
    image?: string | null;
    supplier?: string | null;
    barcode?: string | null;
    barcodes?: string[];
};

const page = useModulePage('promotions');
const { t } = useLocale();
const { can } = usePermissions();

const props = defineProps<{
    promotion: PromotionRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    products: PromotionProductOption[];
    categories: Array<{ id: string; name: string; category_code: string }>;
}>();

const isEdit = computed(() => !!props.promotion);
const pageTitle = useFormPageTitle('promotion', isEdit);

const form = useForm<PromotionFormData>(defaultPromotionForm(props.promotion, props.companies[0]?.id ?? ''));

const productSearch = ref('');
const productPickerOpen = ref(false);
const productPickerRoot = ref<HTMLElement | null>(null);
const pendingProductIds = ref<string[]>([]);
const selectedProductPage = ref(1);
const selectedProductPageSize = ref(20);
const categorySearch = ref('');
const categoryPage = ref(1);
const categoryPageSize = ref(20);
const saveMenuOpen = ref(false);

const showProductSelect = computed(() => form.applies_to === 'products');
const showCategorySelect = computed(() => form.applies_to === 'categories');

const allSaveStatusOptions: Array<{ value: PromotionStatus; label: string; permission: string }> = [
    { value: 'draft', label: 'Save as Draft', permission: 'promotions.draft' },
    { value: 'active', label: 'Save as Active', permission: 'promotions.active' },
    { value: 'expired', label: 'Save as Expired', permission: 'promotions.expired' },
    { value: 'cancelled', label: 'Save as Cancelled', permission: 'promotions.cancelled' },
];

const saveStatusOptions = computed(() =>
    allSaveStatusOptions.filter((option) => can(option.permission)),
);

const primarySaveOption = computed(() =>
    saveStatusOptions.value.find((option) => option.value === form.status) ?? saveStatusOptions.value[0] ?? null,
);

const canSavePromotion = computed(() => saveStatusOptions.value.length > 0);

watch(
    saveStatusOptions,
    (options) => {
        if (!options.length) {
            return;
        }

        if (!options.some((option) => option.value === form.status)) {
            form.status = options[0].value;
        }
    },
    { immediate: true },
);

const productQuery = computed(() => productSearch.value.trim().toLowerCase());

const filteredProducts = computed(() => {
    const query = productQuery.value;
    if (!query) {
        return props.products;
    }

    return props.products.filter((product) => {
        if (product.name.toLowerCase().includes(query) || product.sku.toLowerCase().includes(query)) {
            return true;
        }

        if (product.barcode?.toLowerCase().includes(query)) {
            return true;
        }

        return (product.barcodes ?? []).some((barcode) => barcode.toLowerCase().includes(query));
    });
});

const selectedProducts = computed(() =>
    props.products.filter((product) => form.product_ids.includes(product.id)),
);

const pagedSelectedProducts = computed(() => {
    const start = (selectedProductPage.value - 1) * selectedProductPageSize.value;

    return selectedProducts.value.slice(start, start + selectedProductPageSize.value);
});

const allSearchResultsSelected = computed(() =>
    filteredProducts.value.length > 0
    && filteredProducts.value.every((product) => pendingProductIds.value.includes(product.id)),
);

const filteredCategories = computed(() => {
    const query = categorySearch.value.trim().toLowerCase();
    if (!query) {
        return props.categories;
    }

    return props.categories.filter(
        (category) =>
            category.name.toLowerCase().includes(query)
            || category.category_code.toLowerCase().includes(query),
    );
});

const pagedCategories = computed(() => {
    const start = (categoryPage.value - 1) * categoryPageSize.value;

    return filteredCategories.value.slice(start, start + categoryPageSize.value);
});

const allVisibleCategoriesSelected = computed(() =>
    pagedCategories.value.length > 0
    && pagedCategories.value.every((category) => form.category_ids.includes(category.id)),
);

const selectedProductColumns = computed(() => [
    { key: 'image', label: '', class: 'w-16 min-w-16', minWidth: 64, sortable: false, resizable: false, movable: false },
    { key: 'sku', label: t('fields.sku'), class: 'w-44 min-w-44', minWidth: 176 },
    { key: 'name', label: t('fields.name'), minWidth: 220 },
    { key: 'barcode', label: t('fields.barcode'), class: 'w-40 min-w-40', minWidth: 160 },
    { key: 'supplier', label: t('fields.supplier'), minWidth: 180 },
    { key: 'cost', label: t('fields.cost'), class: 'w-28 min-w-28 text-right', minWidth: 112 },
    { key: 'stock', label: t('fields.stock'), class: 'w-28 min-w-28 text-right', minWidth: 112 },
    { key: 'actions', label: '', class: 'w-12 min-w-12 text-right', minWidth: 48, sortable: false, resizable: false, movable: false },
]);

const categoryColumns = computed(() => [
    { key: 'select', label: '', class: 'w-12 min-w-12', minWidth: 48, sticky: 'start' as const, sortable: false, resizable: false, movable: false },
    { key: 'name', label: t('fields.name'), minWidth: 280 },
    { key: 'category_code', label: t('common.code'), class: 'w-48 min-w-48', minWidth: 192 },
]);

watch(productSearch, () => {
    pendingProductIds.value = [];
    if (!productPickerOpen.value && productSearch.value) {
        productPickerOpen.value = true;
    }
});

watch(categorySearch, () => {
    categoryPage.value = 1;
});

watch(
    () => form.applies_to,
    (value) => {
        if (value !== 'products') {
            form.product_ids = [];
            productSearch.value = '';
            pendingProductIds.value = [];
            productPickerOpen.value = false;
            selectedProductPage.value = 1;
        }
        if (value !== 'categories') {
            form.category_ids = [];
            categorySearch.value = '';
            categoryPage.value = 1;
        }
    },
);

watch(selectedProducts, (rows) => {
    const lastPage = Math.max(1, Math.ceil(rows.length / selectedProductPageSize.value));
    if (selectedProductPage.value > lastPage) {
        selectedProductPage.value = lastPage;
    }
});

watch(filteredCategories, (rows) => {
    const lastPage = Math.max(1, Math.ceil(rows.length / categoryPageSize.value));
    if (categoryPage.value > lastPage) {
        categoryPage.value = lastPage;
    }
});

function openProductPicker() {
    productPickerOpen.value = true;
}

function closeProductPicker() {
    productPickerOpen.value = false;
}

function onDocumentPointerDown(event: MouseEvent) {
    const target = event.target as Node | null;
    if (!productPickerOpen.value || !productPickerRoot.value || !target) {
        return;
    }

    if (!productPickerRoot.value.contains(target)) {
        closeProductPicker();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onDocumentPointerDown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onDocumentPointerDown);
});

function togglePendingProduct(id: string) {
    const index = pendingProductIds.value.indexOf(id);
    if (index >= 0) {
        pendingProductIds.value.splice(index, 1);
    } else {
        pendingProductIds.value.push(id);
    }
}

function toggleAllSearchResults() {
    if (allSearchResultsSelected.value) {
        const visibleIds = new Set(filteredProducts.value.map((product) => product.id));
        pendingProductIds.value = pendingProductIds.value.filter((id) => !visibleIds.has(id));
        return;
    }

    const next = new Set(pendingProductIds.value);
    filteredProducts.value.forEach((product) => next.add(product.id));
    pendingProductIds.value = Array.from(next);
}

function addSelectedProducts() {
    if (!pendingProductIds.value.length) {
        return;
    }

    const next = new Set(form.product_ids);
    pendingProductIds.value.forEach((id) => next.add(id));
    form.product_ids = Array.from(next);
    pendingProductIds.value = [];
    productSearch.value = '';
    selectedProductPage.value = 1;
    closeProductPicker();
}

function removeSelectedProduct(id: string) {
    form.product_ids = form.product_ids.filter((productId) => productId !== id);
}

function clearSelectedProducts() {
    form.product_ids = [];
    selectedProductPage.value = 1;
}

function toggleCategoryId(id: string) {
    const index = form.category_ids.indexOf(id);
    if (index >= 0) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(id);
    }
}

function toggleAllVisibleCategories() {
    if (allVisibleCategoriesSelected.value) {
        const visibleIds = new Set(pagedCategories.value.map((category) => category.id));
        form.category_ids = form.category_ids.filter((id) => !visibleIds.has(id));
        return;
    }

    const next = new Set(form.category_ids);
    pagedCategories.value.forEach((category) => next.add(category.id));
    form.category_ids = Array.from(next);
}

function clearSelectedCategories() {
    form.category_ids = [];
}

function formatMoney(value: string | number | null | undefined): string {
    const amount = Number(value ?? 0);

    return `₱${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatStock(value: string | number | null | undefined): string {
    const qty = Number(value ?? 0);

    return Number.isInteger(qty) ? String(qty) : qty.toFixed(2);
}

async function saveWithStatus(status: PromotionStatus) {
    if (!can(`promotions.${status}`) || !primarySaveOption.value) {
        return;
    }

    form.status = status;
    saveMenuOpen.value = false;

    const confirmed = await confirmSave(
        primarySaveOption.value.label,
        t('entities.promotion'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.promotions.update', props.promotion!.id));
    } else {
        form.post(route('admin.promotions.store'));
    }
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <div class="promo-form">
            <form class="promo-form__body" @submit.prevent="saveWithStatus(form.status as PromotionStatus)">
                <IndexPageHeader
                    class="shrink-0"
                    layout="inline"
                    :back-href="route('admin.promotions.index')"
                    :eyebrow="page.eyebrow"
                    :title="pageTitle"
                    :description="t('forms.subtitles.promotions')"
                >
                    <template #actions>
                        <Link :href="route('admin.promotions.index')">
                            <Button type="button" variant="secondary">{{ t('common.cancel') }}</Button>
                        </Link>

                        <div v-if="canSavePromotion" class="promo-save-split">
                            <Button
                                type="button"
                                class="promo-save-split__main"
                                :disabled="form.processing"
                                @click="saveWithStatus(form.status as PromotionStatus)"
                            >
                                {{ form.processing ? t('common.saving') : primarySaveOption?.label }}
                            </Button>
                            <Dropdown
                                v-if="saveStatusOptions.length > 1"
                                v-model:open="saveMenuOpen"
                                class="promo-save-split__dropdown"
                                align="right"
                                width="52"
                                content-classes="py-1 bg-surface"
                            >
                                <template #trigger>
                                    <Button
                                        type="button"
                                        class="promo-save-split__toggle"
                                        :disabled="form.processing"
                                        aria-label="Save status options"
                                    >
                                        <NavIcon name="chevron-down" subtle />
                                    </Button>
                                </template>
                                <template #content>
                                    <button
                                        v-for="option in saveStatusOptions"
                                        :key="option.value"
                                        type="button"
                                        class="promo-save-option"
                                        :class="{ 'promo-save-option--active': form.status === option.value }"
                                        @click="saveWithStatus(option.value)"
                                    >
                                        {{ option.label }}
                                    </button>
                                </template>
                            </Dropdown>
                        </div>
                    </template>
                </IndexPageHeader>

                <div class="promo-form__fields">
                    <div class="form-grid-3">
                        <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                            <Select v-model="form.company_id">
                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                    {{ company.display_name || company.name }}
                                </option>
                            </Select>
                        </FormField>

                        <FormField label-key="code" required :error="form.errors.promo_code">
                            <Input v-model="form.promo_code" />
                        </FormField>

                        <FormField label-key="name" required :error="form.errors.name">
                            <Input v-model="form.name" />
                        </FormField>

                        <FormField label-key="promoType" required :error="form.errors.promo_type">
                            <Select v-model="form.promo_type">
                                <option value="percent_off">Percent off</option>
                                <option value="fixed_amount">Fixed amount</option>
                            </Select>
                        </FormField>

                        <FormField label-key="discountValue" required :error="form.errors.discount_value">
                            <Input
                                v-model="form.discount_value"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                        </FormField>

                        <FormField label-key="amount" :error="form.errors.min_purchase_amount">
                            <Input v-model="form.min_purchase_amount" type="number" step="0.01" min="0" :placeholder="t('fields.optional')" />
                        </FormField>

                        <FormField label-key="appliesTo" required :error="form.errors.applies_to">
                            <Select v-model="form.applies_to">
                                <option value="all">All products</option>
                                <option value="products">Selected products</option>
                                <option value="categories">Selected categories</option>
                            </Select>
                        </FormField>

                        <FormField label-key="orderDate" :error="form.errors.starts_at">
                            <Input v-model="form.starts_at" type="datetime-local" />
                        </FormField>

                        <FormField label-key="toDate" :error="form.errors.ends_at">
                            <Input v-model="form.ends_at" type="datetime-local" />
                        </FormField>

                        <FormField label-key="quantity" :error="form.errors.usage_limit">
                            <Input v-model="form.usage_limit" type="number" step="1" min="1" :placeholder="t('fields.optional')" />
                        </FormField>
                    </div>
                    <p v-if="form.errors.status" class="form-error mt-2">{{ form.errors.status }}</p>
                </div>

                <div v-if="showProductSelect" class="promo-scope">
                    <div class="promo-scope__toolbar promo-scope__toolbar--products">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-ink">{{ t('fields.product') }}</h3>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ form.product_ids.length }} selected
                                <span v-if="form.errors.product_ids" class="form-error"> · {{ form.errors.product_ids }}</span>
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="!form.product_ids.length"
                            @click="clearSelectedProducts"
                        >
                            Clear
                        </Button>
                    </div>

                    <div ref="productPickerRoot" class="promo-product-float">
                        <div class="promo-product-picker__search promo-product-picker__search--bar">
                            <span class="promo-product-picker__search-icon" aria-hidden="true">
                                <NavIcon name="search" subtle />
                            </span>
                            <input
                                v-model="productSearch"
                                type="search"
                                class="promo-product-picker__input"
                                placeholder="Search by name, SKU, or barcode"
                                autocomplete="off"
                                @focus="openProductPicker"
                                @click="openProductPicker"
                            />
                            <button
                                v-if="productSearch"
                                type="button"
                                class="promo-product-picker__clear"
                                aria-label="Clear search"
                                @click="productSearch = ''"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="productPickerOpen" class="promo-product-float__panel">
                            <div class="promo-product-picker__results-header">
                                <div class="min-w-0">
                                    <p class="promo-product-picker__eyebrow">Search results</p>
                                    <p class="mt-0.5 text-xs text-ink-muted">
                                        <template v-if="productQuery">
                                            {{ filteredProducts.length }}
                                            {{ filteredProducts.length === 1 ? 'match' : 'matches' }}
                                            · “{{ productSearch.trim() }}”
                                        </template>
                                        <template v-else>
                                            {{ filteredProducts.length }}
                                            {{ filteredProducts.length === 1 ? 'product' : 'products' }}
                                        </template>
                                    </p>
                                </div>
                                <label class="promo-product-picker__select-all">
                                    <input
                                        type="checkbox"
                                        class="rounded border-line text-accent"
                                        :checked="allSearchResultsSelected"
                                        :disabled="!filteredProducts.length"
                                        @change="toggleAllSearchResults"
                                    />
                                    <span>Select all</span>
                                </label>
                            </div>

                            <div v-if="!filteredProducts.length" class="promo-product-picker__hint">
                                {{ t('common.noResults') }}
                            </div>
                            <div v-else class="promo-product-picker__list">
                                <label
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    class="promo-product-picker__row"
                                    :class="{
                                        'promo-product-picker__row--checked': pendingProductIds.includes(product.id),
                                        'promo-product-picker__row--added': form.product_ids.includes(product.id),
                                    }"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded border-line text-accent"
                                        :checked="pendingProductIds.includes(product.id)"
                                        @change="togglePendingProduct(product.id)"
                                    />
                                    <img
                                        v-if="product.image"
                                        :src="product.image"
                                        :alt="product.name"
                                        class="promo-product-picker__thumb"
                                    />
                                    <div v-else class="promo-product-picker__thumb promo-product-picker__thumb--empty">
                                        <NavIcon name="products" subtle />
                                    </div>
                                    <div class="promo-product-picker__body">
                                        <div class="promo-product-picker__main">
                                            <div class="min-w-0">
                                                <p class="promo-product-picker__sku">{{ product.sku }}</p>
                                                <p class="promo-product-picker__name">{{ product.name }}</p>
                                            </div>
                                            <span class="promo-product-picker__stock">
                                                Stock: {{ formatStock(product.qty) }}
                                            </span>
                                        </div>
                                        <p class="promo-product-picker__meta">
                                            <span>Supplier: {{ product.supplier || '—' }}</span>
                                            <span class="promo-product-picker__sep" aria-hidden="true" />
                                            <span>Barcode: {{ product.barcode || '—' }}</span>
                                            <span class="promo-product-picker__sep" aria-hidden="true" />
                                            <span>Cost: {{ formatMoney(product.cost) }}</span>
                                        </p>
                                        <p v-if="form.product_ids.includes(product.id)" class="promo-product-picker__added">
                                            Already added
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="promo-product-picker__footer">
                                <span class="text-sm text-ink-muted">
                                    Selected: <span class="font-semibold text-ink">{{ pendingProductIds.length }}</span>
                                </span>
                                <Button
                                    type="button"
                                    :disabled="!pendingProductIds.length"
                                    @click="addSelectedProducts"
                                >
                                    + Add selected
                                </Button>
                            </div>
                        </div>
                    </div>

                    <EmptyState
                        v-if="!selectedProducts.length"
                        class="min-h-0 flex-1"
                        title="No products added"
                        description="Search and add products to this promotion."
                    />
                    <DataTable
                        v-else
                        :columns="selectedProductColumns"
                        :rows="pagedSelectedProducts"
                        compact
                        sticky-header
                        class="promo-scope__table"
                    >
                        <template #cell-image="{ row }">
                            <img
                                v-if="(row as PromotionProductOption).image"
                                :src="(row as PromotionProductOption).image!"
                                :alt="(row as PromotionProductOption).name"
                                class="h-9 w-9 rounded-lg object-cover"
                            />
                            <div v-else class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-muted text-ink-muted">
                                <NavIcon name="products" subtle />
                            </div>
                        </template>
                        <template #cell-sku="{ row }">
                            <span class="font-medium tabular-nums text-ink">{{ (row as PromotionProductOption).sku }}</span>
                        </template>
                        <template #cell-name="{ row }">
                            <span class="font-medium text-ink">{{ (row as PromotionProductOption).name }}</span>
                        </template>
                        <template #cell-barcode="{ row }">
                            <span class="tabular-nums text-ink-muted">{{ (row as PromotionProductOption).barcode || '—' }}</span>
                        </template>
                        <template #cell-supplier="{ row }">
                            <span class="text-ink-muted">{{ (row as PromotionProductOption).supplier || '—' }}</span>
                        </template>
                        <template #cell-cost="{ row }">
                            <span class="tabular-nums text-ink">{{ formatMoney((row as PromotionProductOption).cost) }}</span>
                        </template>
                        <template #cell-stock="{ row }">
                            <span class="promo-product-picker__stock">
                                Stock: {{ formatStock((row as PromotionProductOption).qty) }}
                            </span>
                        </template>
                        <template #cell-actions="{ row }">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md p-1.5 text-ink-muted transition hover:bg-surface-muted hover:text-red-600 dark:hover:text-red-400"
                                :aria-label="t('common.remove')"
                                @click="removeSelectedProduct((row as PromotionProductOption).id)"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </template>
                        <template #footer>
                            <TablePagination
                                :total="selectedProducts.length"
                                :page="selectedProductPage"
                                :page-size="selectedProductPageSize"
                                @update:page="selectedProductPage = $event"
                                @update:page-size="selectedProductPageSize = $event; selectedProductPage = 1"
                            />
                        </template>
                    </DataTable>
                </div>

                <div v-if="showCategorySelect" class="promo-scope">
                    <div class="promo-scope__toolbar">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-ink">{{ t('fields.category') }}</h3>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ form.category_ids.length }} selected
                                <span v-if="form.errors.category_ids" class="form-error"> · {{ form.errors.category_ids }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Input
                                v-model="categorySearch"
                                class="w-full sm:w-56"
                                :placeholder="t('common.search')"
                            />
                            <Button type="button" variant="secondary" :disabled="!form.category_ids.length" @click="clearSelectedCategories">
                                Clear
                            </Button>
                        </div>
                    </div>

                    <EmptyState
                        v-if="!filteredCategories.length"
                        class="min-h-0 flex-1"
                        :title="t('common.noResults')"
                    />
                    <DataTable
                        v-else
                        :columns="categoryColumns"
                        :rows="pagedCategories"
                        compact
                        sticky-header
                        class="promo-scope__table"
                    >
                        <template #header-select>
                            <input
                                type="checkbox"
                                class="rounded border-line"
                                :checked="allVisibleCategoriesSelected"
                                @change="toggleAllVisibleCategories"
                            />
                        </template>
                        <template #cell-select="{ row }">
                            <input
                                type="checkbox"
                                class="rounded border-line"
                                :checked="form.category_ids.includes(row.id)"
                                @change="toggleCategoryId(row.id)"
                            />
                        </template>
                        <template #cell-name="{ row }">
                            <button
                                type="button"
                                class="block w-full truncate text-left font-medium text-ink hover:text-accent"
                                @click="toggleCategoryId(row.id)"
                            >
                                {{ row.name }}
                            </button>
                        </template>
                        <template #cell-category_code="{ row }">
                            <span class="tabular-nums text-ink-muted">{{ row.category_code }}</span>
                        </template>
                        <template #footer>
                            <TablePagination
                                :total="filteredCategories.length"
                                :page="categoryPage"
                                :page-size="categoryPageSize"
                                @update:page="categoryPage = $event"
                                @update:page-size="categoryPageSize = $event; categoryPage = 1"
                            />
                        </template>
                    </DataTable>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
