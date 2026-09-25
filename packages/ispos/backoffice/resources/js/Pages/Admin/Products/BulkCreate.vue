<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormField from '@/Components/forms/FormField.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Modal from '@/Components/ui/Modal.vue';
import Select from '@/Components/ui/Select.vue';
import TablePagination from '@/Components/ui/TablePagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import {
    defaultProductBulkCreateForm,
    emptyProductBulkItem,
    emptyComponentRow,
    emptyIngredientRow,
    emptyModifierGroupRow,
    emptyModifierOptionRow,
    emptyVariantRow,
    nextClientKey,
    type ProductBulkCreateFormData,
    type ProductBulkItemRow,
} from '@/types/product';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type InputMethod = 'csv' | 'bulk-form' | 'manual';
type SidebarTab = 'details' | 'variants' | 'modifiers' | 'components';

interface DisplayRow {
    item: ProductBulkItemRow;
    index: number;
}

const page = useModulePage('products');
const { t, hint } = useLocale();

const props = defineProps<{
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    categories: Array<{ id: string; name: string; category_code: string; company_id: string }>;
    brands: Array<{ id: string; name: string; brand_code: string; company_id: string }>;
    units: Array<{ id: string; name: string; symbol: string | null; company_id: string }>;
    taxes: Array<{ id: string; name: string; rate: number | string; company_id: string }>;
    salesPlans: Array<{ id: string; name: string; plan_code: string; company_id: string; status: string }>;
    ingredientProducts: Array<{ id: string; name: string; sku: string; company_id: string }>;
    componentProducts: Array<{ id: string; name: string; sku: string; company_id: string }>;
}>();

const pageTitle = computed(() => t('pages.products.bulkCreateTitle'));

const form = useForm<ProductBulkCreateFormData>(defaultProductBulkCreateForm(props.companies[0]?.id ?? ''));

const inputMethod = ref<InputMethod>('bulk-form');
const sidebarTab = ref<SidebarTab>('details');
const selectedIndex = ref(0);
const selectedRows = ref<Set<number>>(new Set());
const selectionExplicit = ref(false);
const submittedRows = ref<ProductBulkItemRow[]>([]);
const searchQuery = ref('');
const filterCategory = ref('');
const filterStatus = ref('');
const tablePage = ref(1);
const tablePageSize = ref(50);
const csvInputRef = ref<HTMLInputElement | null>(null);
const imageInputRef = ref<HTMLInputElement | null>(null);
const relationModal = ref<{ type: 'variants' | 'modifiers' | 'components' | 'ingredients'; index: number } | null>(null);
const csvFeedback = ref<{ kind: 'error' | 'success'; message: string } | null>(null);
const catalogOptionsLoading = ref(false);
let catalogReloadSequence = 0;

const filteredCategories = computed(() =>
    props.categories.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredBrands = computed(() =>
    props.brands.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredUnits = computed(() =>
    props.units.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredTaxes = computed(() =>
    props.taxes.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredSalesPlans = computed(() =>
    props.salesPlans.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredIngredientProducts = computed(() =>
    props.ingredientProducts.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredComponentProducts = computed(() =>
    props.componentProducts.filter((item) => !form.company_id || item.company_id === form.company_id),
);

const globalsReady = computed(
    () => Boolean(form.company_id && form.sales_plan_id && form.product_type && form.status),
);

const selectedItem = computed(() => form.items[selectedIndex.value] ?? emptyProductBulkItem());

const filledRowIndices = computed(() =>
    form.items
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => item.sku.trim() || item.name.trim())
        .map(({ index }) => index),
);
const filledRowIndexSet = computed(() => new Set(filledRowIndices.value));

const filteredRows = computed<DisplayRow[]>(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return form.items
        .map((item, index) => ({ item, index }))
        .filter(({ item, index }) => {
            if (query) {
                const haystack = `${item.name} ${item.sku}`.toLowerCase();
                if (!haystack.includes(query)) {
                    return false;
                }
            }

            if (filterCategory.value && item.category_id !== filterCategory.value) {
                return false;
            }

            if (filterStatus.value && form.status !== filterStatus.value) {
                return false;
            }

            return item.sku.trim() || item.name.trim() || index === selectedIndex.value;
        });
});

const paginatedRows = computed(() => {
    const start = (tablePage.value - 1) * tablePageSize.value;
    return filteredRows.value.slice(start, start + tablePageSize.value);
});
const visibleFilledRows = computed(() =>
    paginatedRows.value.filter(({ index }) => filledRowIndexSet.value.has(index)),
);

const selectedCount = computed(() => {
    if (!selectionExplicit.value) {
        return filledRowIndices.value.length;
    }

    return [...selectedRows.value].filter((index) => filledRowIndexSet.value.has(index)).length;
});

const createButtonLabel = computed(() =>
    t('pages.products.bulkCreateSubmit', { count: selectedCount.value }),
);

const allVisibleSelected = computed(() => {
    if (!visibleFilledRows.value.length) {
        return false;
    }

    return visibleFilledRows.value.every(({ index }) => isRowSelected(index));
});

watch(
    () => form.company_id,
    (companyId, previousCompanyId) => {
        csvFeedback.value = null;
        if (form.sales_plan_id && !filteredSalesPlans.value.some((plan) => plan.id === form.sales_plan_id)) {
            form.sales_plan_id = filteredSalesPlans.value.length === 1 ? filteredSalesPlans.value[0].id : '';
        }
        if (form.tax_id && !filteredTaxes.value.some((item) => item.id === form.tax_id)) {
            form.tax_id = '';
        }

        form.items.forEach((item) => {
            if (item.category_id && !filteredCategories.value.some((category) => category.id === item.category_id)) {
                item.category_id = '';
            }
            if (item.brand_id && !filteredBrands.value.some((brand) => brand.id === item.brand_id)) {
                item.brand_id = '';
            }
            if (item.unit_id && !filteredUnits.value.some((unit) => unit.id === item.unit_id)) {
                item.unit_id = '';
            }
            item.components.forEach((component) => {
                if (component.component_product_id && !filteredComponentProducts.value.some((product) => product.id === component.component_product_id)) {
                    component.component_product_id = '';
                }
                if (component.unit_id && !filteredUnits.value.some((unit) => unit.id === component.unit_id)) {
                    component.unit_id = '';
                }
            });
            item.ingredients.forEach((ingredient) => {
                if (ingredient.ingredient_product_id && !filteredIngredientProducts.value.some((product) => product.id === ingredient.ingredient_product_id)) {
                    ingredient.ingredient_product_id = '';
                }
                if (ingredient.unit_id && !filteredUnits.value.some((unit) => unit.id === ingredient.unit_id)) {
                    ingredient.unit_id = '';
                }
            });
        });

        if (companyId && companyId !== previousCompanyId) {
            catalogOptionsLoading.value = true;
            const reloadSequence = ++catalogReloadSequence;
            router.get(
                route('admin.products.bulk-create'),
                { company_id: companyId },
                {
                    only: ['categories', 'brands', 'units', 'taxes', 'ingredientProducts', 'componentProducts', 'salesPlans'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    onFinish: () => {
                        if (reloadSequence === catalogReloadSequence) {
                            catalogOptionsLoading.value = false;
                        }
                    },
                },
            );
        }
    },
);

watch(
    filteredSalesPlans,
    (plans) => {
        if (!form.sales_plan_id && plans.length === 1) {
            form.sales_plan_id = plans[0].id;
        }
    },
    { immediate: true },
);

watch(filledRowIndices, (indices) => {
    if (!indices.length) {
        selectedRows.value = new Set();
        return;
    }

    const validIndices = new Set(indices);
    const next = new Set([...selectedRows.value].filter((index) => validIndices.has(index)));
    selectedRows.value = next;
});

watch(filteredRows, () => {
    if (tablePage.value > 1 && paginatedRows.value.length === 0) {
        tablePage.value = 1;
    }
});

function companyLabel(company: (typeof props.companies)[number]): string {
    return company.display_name || company.name;
}

function categoryName(categoryId: string): string {
    return props.categories.find((category) => category.id === categoryId)?.name ?? '—';
}

function brandName(brandId: string): string {
    return props.brands.find((brand) => brand.id === brandId)?.name ?? '—';
}
function taxName(taxId: string): string {
    const tax = props.taxes.find((item) => item.id === taxId);
    return tax ? `${tax.name} (${tax.rate}%)` : '—';
}

function unitLabel(unitId: string): string {
    const unit = props.units.find((item) => item.id === unitId);
    if (!unit) {
        return t('fields.none');
    }

    return unit.symbol ? `${unit.name} (${unit.symbol})` : unit.name;
}

function formatMoney(value: string | number): string {
    const amount = Number(value);
    if (Number.isNaN(amount)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}

function stockLabel(item: ProductBulkItemRow): string {
    if (!form.track_inventory) {
        return '—';
    }

    return item.ideal_qty.trim() || '0';
}

function selectRow(index: number) {
    selectedIndex.value = index;
    sidebarTab.value = 'details';
}

function openRelationModal(type: 'variants' | 'modifiers' | 'components' | 'ingredients', index: number) {
    relationModal.value = { type, index };
}

function relationRows(): Array<Record<string, any>> {
    if (!relationModal.value) return [];
    const item = form.items[relationModal.value.index];
    if (!item) return [];
    if (relationModal.value.type === 'variants') return item.variants as Array<Record<string, any>>;
    if (relationModal.value.type === 'modifiers') return item.modifier_groups as Array<Record<string, any>>;
    if (relationModal.value.type === 'components') return item.components as Array<Record<string, any>>;
    return item.ingredients as Array<Record<string, any>>;
}

function toggleRowSelection(index: number) {
    if (!filledRowIndexSet.value.has(index)) {
        return;
    }

    const next = selectionExplicit.value ? new Set(selectedRows.value) : new Set(filledRowIndices.value);
    if (next.has(index)) {
        next.delete(index);
    } else {
        next.add(index);
    }
    selectionExplicit.value = true;
    selectedRows.value = next;
}

function toggleSelectAllVisible() {
    const next = selectionExplicit.value ? new Set(selectedRows.value) : new Set(filledRowIndices.value);
    if (allVisibleSelected.value) {
        visibleFilledRows.value.forEach(({ index }) => next.delete(index));
    } else {
        visibleFilledRows.value.forEach(({ index }) => next.add(index));
    }
    selectionExplicit.value = true;
    selectedRows.value = next;
}

function clearSelection() {
    selectionExplicit.value = true;
    selectedRows.value = new Set();
}

function isRowSelected(index: number): boolean {
    return filledRowIndexSet.value.has(index) && (!selectionExplicit.value || selectedRows.value.has(index));
}

function clearFilters() {
    searchQuery.value = '';
    filterCategory.value = '';
    filterStatus.value = '';
    tablePage.value = 1;
}

function addRow(select = true) {
    form.items.push(emptyProductBulkItem());
    const index = form.items.length - 1;
    if (select) {
        selectRow(index);
    }
    selectedRows.value = new Set([...selectedRows.value, index]);
}

function addToList() {
    addRow(true);
}

function addVariant() {
    selectedItem.value.variants.push(emptyVariantRow());
}

function removeVariant(index: number) {
    selectedItem.value.variants.splice(index, 1);
}

function addModifierGroup() {
    selectedItem.value.modifier_groups.push(emptyModifierGroupRow());
}

function removeModifierGroup(index: number) {
    selectedItem.value.modifier_groups.splice(index, 1);
}

function addModifierOption(groupIndex: number) {
    selectedItem.value.modifier_groups[groupIndex].options.push(emptyModifierOptionRow());
}

function removeModifierOption(groupIndex: number, optionIndex: number) {
    selectedItem.value.modifier_groups[groupIndex].options.splice(optionIndex, 1);
}

function addComponent() {
    selectedItem.value.components.push(emptyComponentRow());
}

function removeComponent(index: number) {
    selectedItem.value.components.splice(index, 1);
}

function addIngredient() {
    selectedItem.value.ingredients.push(emptyIngredientRow());
}

function removeIngredient(index: number) {
    selectedItem.value.ingredients.splice(index, 1);
}

watch(
    () => selectedItem.value.has_variants,
    (enabled) => {
        if (enabled && !selectedItem.value.variants.length) {
            selectedItem.value.variants.push(emptyVariantRow());
        }
        if (enabled) sidebarTab.value = 'variants';
        if (!enabled && sidebarTab.value === 'variants') sidebarTab.value = 'details';
    },
);

watch(
    () => selectedItem.value.has_modifiers,
    (enabled) => {
        if (enabled) sidebarTab.value = 'modifiers';
        if (!enabled && sidebarTab.value === 'modifiers') sidebarTab.value = 'details';
    },
);

watch(
    () => selectedItem.value.has_components,
    (enabled) => {
        if (enabled) sidebarTab.value = 'components';
        if (!enabled && sidebarTab.value === 'components') sidebarTab.value = 'details';
    },
);

watch(
    () => form.product_type,
    (newType) => {
        if (newType !== 'menu_item') {
            selectedItem.value.has_modifiers = false;
            selectedItem.value.has_components = false;
        }
        if (newType === 'ingredient') {
            selectedItem.value.has_variants = false;
            selectedItem.value.variants = [];
        }
        if (sidebarTab.value !== 'details') sidebarTab.value = 'details';
    },
);

function removeRow(index: number) {
    const removedItem = form.items[index];
    if (removedItem) {
        releaseImagePreviews(removedItem);
    }

    if (form.items.length === 1) {
        form.items = [emptyProductBulkItem()];
        selectedIndex.value = 0;
        selectedRows.value = new Set();
        return;
    }

    form.items.splice(index, 1);
    if (selectedIndex.value === index) {
        selectedIndex.value = Math.min(index, form.items.length - 1);
    } else if (selectedIndex.value > index) {
        selectedIndex.value -= 1;
    }

    const nextSelection = new Set<number>();
    selectedRows.value.forEach((rowIndex) => {
        if (rowIndex < index) {
            nextSelection.add(rowIndex);
        } else if (rowIndex > index) {
            nextSelection.add(rowIndex - 1);
        }
    });
    selectedRows.value = nextSelection;
}

function openImagePicker() {
    imageInputRef.value?.click();
}

function onImageSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';
    if (!files.length) {
        return;
    }

    const remaining = 10 - selectedItem.value.images.length;
    const nextImages = [...selectedItem.value.images];

    files.slice(0, remaining).forEach((file) => {
        if (!file.type.startsWith('image/') || file.size > 1024 * 1024) {
            return;
        }

        nextImages.push({
            client_key: nextClientKey('img'),
            is_default: nextImages.length === 0,
            sort_order: String(nextImages.length),
            preview_url: URL.createObjectURL(file),
            name: file.name,
            size_bytes: file.size,
            file,
        });
    });

    selectedItem.value.images = nextImages;
    selectedItem.value.image_preview = nextImages.find((image) => image.is_default)?.preview_url ?? null;
}

function removeImage() {
    const mainIndex = selectedItem.value.images.findIndex((image) => image.is_default);
    if (mainIndex >= 0) {
        removeImageAt(mainIndex);
    }
}

function removeImageAt(index: number) {
    const removed = selectedItem.value.images[index];
    if (!removed) {
        return;
    }

    if (removed.preview_url?.startsWith('blob:')) {
        URL.revokeObjectURL(removed.preview_url);
    }

    const nextImages = selectedItem.value.images
        .filter((_, imageIndex) => imageIndex !== index)
        .map((image, imageIndex) => ({
            ...image,
            is_default: removed.is_default ? imageIndex === 0 : image.is_default,
            sort_order: String(imageIndex),
        }));

    selectedItem.value.images = nextImages;
    selectedItem.value.image_preview = nextImages.find((image) => image.is_default)?.preview_url ?? null;
}

function releaseImagePreviews(item: ProductBulkItemRow) {
    item.images.forEach((image) => {
        if (image.preview_url?.startsWith('blob:')) {
            URL.revokeObjectURL(image.preview_url);
        }
    });
}

onBeforeUnmount(() => {
    form.items.forEach(releaseImagePreviews);
});

function setMainImage(index: number) {
    selectedItem.value.images = selectedItem.value.images.map((image, imageIndex) => ({
        ...image,
        is_default: imageIndex === index,
    }));
    selectedItem.value.image_preview = selectedItem.value.images[index]?.preview_url ?? null;
}

function serializeBulkItem(item: ProductBulkItemRow): Record<string, unknown> {
    const uploads: File[] = [];
    const images = item.images.map((image, index) => {
        const upload_index = uploads.length;
        if (image.file) {
            uploads.push(image.file);
        }

        return {
            client_key: image.client_key,
            upload_index,
            is_default: image.is_default,
            sort_order: index,
        };
    });

    return {
        ...item,
        image_preview: undefined,
        images,
        image_uploads: uploads,
    };
}

function downloadCsvSample() {
    const csv = '\uFEFFname,sku,category,brand,unit,cost,base_price,barcode,description,ideal_qty,warning_qty\r\nSample product,SAMPLE-001,,,,0,0,,,,\r\n';
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'products-sample.csv';
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function openCsvPicker() {
    if (!catalogOptionsLoading.value) {
        csvInputRef.value?.click();
    }
}

function parseCsvRecords(source: string): string[][] {
    const text = source.replace(/^\uFEFF/, '');
    const records: string[][] = [];
    let record: string[] = [];
    let field = '';
    let insideQuotes = false;

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];

        if (insideQuotes) {
            if (character === '"' && text[index + 1] === '"') {
                field += '"';
                index += 1;
            } else if (character === '"') {
                insideQuotes = false;
            } else {
                field += character;
            }
            continue;
        }

        if (character === '"' && field === '') {
            insideQuotes = true;
        } else if (character === ',') {
            record.push(field);
            field = '';
        } else if (character === '\r' || character === '\n') {
            record.push(field);
            if (record.some((value) => value.trim() !== '')) {
                records.push(record);
            }
            record = [];
            field = '';
            if (character === '\r' && text[index + 1] === '\n') {
                index += 1;
            }
        } else {
            field += character;
        }
    }

    if (insideQuotes) {
        throw new Error('unclosed_quote');
    }

    record.push(field);
    if (record.some((value) => value.trim() !== '')) {
        records.push(record);
    }

    return records;
}

function normalizeCsvHeader(value: string): string {
    const normalized = value.trim().toLowerCase().replace(/[\s-]+/g, '_');
    const aliases: Record<string, string> = {
        product_name: 'name',
        product_sku: 'sku',
        sku_code: 'sku',
        category_code: 'category',
        brand_code: 'brand',
        unit_name: 'unit',
        unit_symbol: 'unit',
        price: 'base_price',
        selling_price: 'base_price',
    };

    return aliases[normalized] ?? normalized;
}

function normalizeCsvValue(value: string): string {
    return value.trim().toLocaleLowerCase();
}

function resolveCsvReference<T extends { id: string; name: string }>(
    rawValue: string,
    options: T[],
    codeField?: keyof T,
): string | null {
    if (!rawValue.trim()) {
        return '';
    }

    const normalized = normalizeCsvValue(rawValue);
    const codeMatches = codeField
        ? options.filter((option) => normalizeCsvValue(String(option[codeField] ?? '')) === normalized)
        : [];
    const nameMatches = options.filter((option) => normalizeCsvValue(option.name) === normalized);
    const matches = codeMatches.length ? codeMatches : nameMatches;

    return matches.length === 1 ? matches[0]?.id ?? null : null;
}

function isPristineBulkItem(item: ProductBulkItemRow): boolean {
    return !item.name.trim()
        && !item.sku.trim()
        && !item.description.trim()
        && !item.barcode.trim()
        && !item.category_id
        && !item.brand_id
        && !item.unit_id
        && !item.ideal_qty.trim()
        && !item.warning_qty.trim()
        && item.cost === '0'
        && item.base_price === '0'
        && !item.has_variants
        && !item.has_modifiers
        && !item.has_components
        && item.variants.length === 0
        && item.modifier_groups.length === 0
        && item.components.length === 0
        && item.ingredients.length === 0
        && item.images.length === 0;
}

async function importCsvFile(file: File) {
    csvFeedback.value = null;
    if (catalogOptionsLoading.value) {
        return;
    }

    if (!file.name.toLowerCase().endsWith('.csv') && file.type !== 'text/csv') {
        csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvInvalidFile') };
        return;
    }

    let records: string[][];
    try {
        records = parseCsvRecords(await file.text());
    } catch {
        csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvReadError') };
        return;
    }

    if (records.length < 2) {
        csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvHeaderError') };
        return;
    }

    const headers = records[0].map(normalizeCsvHeader);
    const columns = new Map<string, number>();
    for (const [index, header] of headers.entries()) {
        if (!header) {
            continue;
        }
        if (columns.has(header)) {
            csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvReadError') };
            return;
        }
        columns.set(header, index);
    }

    if (!columns.has('name') || !columns.has('sku')) {
        csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvHeaderError') };
        return;
    }

    const getValue = (record: string[], column: string) => {
        const index = columns.get(column);
        return index === undefined ? '' : (record[index] ?? '').trim();
    };
    const importedItems: ProductBulkItemRow[] = [];
    const invalidRows = new Set<number>();
    const seenSkus = new Set(form.items.map((item) => normalizeCsvValue(item.sku)).filter(Boolean));

    records.slice(1).forEach((record, recordIndex) => {
        const lineNumber = recordIndex + 2;
        const name = getValue(record, 'name');
        const sku = getValue(record, 'sku');
        if (!name || !sku) {
            invalidRows.add(lineNumber);
            return;
        }

        const normalizedSku = normalizeCsvValue(sku);
        if (seenSkus.has(normalizedSku)) {
            invalidRows.add(lineNumber);
        }
        seenSkus.add(normalizedSku);

        const categoryId = resolveCsvReference(getValue(record, 'category'), filteredCategories.value, 'category_code');
        const brandId = resolveCsvReference(getValue(record, 'brand'), filteredBrands.value, 'brand_code');
        const unitId = resolveCsvReference(getValue(record, 'unit'), filteredUnits.value, 'symbol');
        if (categoryId === null || brandId === null || unitId === null) {
            invalidRows.add(lineNumber);
        }

        const numericValues = ['cost', 'base_price', 'ideal_qty', 'warning_qty'].map((column) => getValue(record, column));
        if (numericValues.some((value) => value !== '' && (!Number.isFinite(Number(value)) || Number(value) < 0))) {
            invalidRows.add(lineNumber);
        }

        const item = emptyProductBulkItem();
        item.name = name;
        item.sku = sku;
        item.description = getValue(record, 'description');
        item.barcode = getValue(record, 'barcode');
        item.category_id = categoryId ?? '';
        item.brand_id = brandId ?? '';
        item.unit_id = unitId ?? '';
        item.cost = getValue(record, 'cost') || '0';
        item.base_price = getValue(record, 'base_price') || '0';
        item.ideal_qty = getValue(record, 'ideal_qty');
        item.warning_qty = getValue(record, 'warning_qty');
        importedItems.push(item);
    });

    if (invalidRows.size) {
        csvFeedback.value = {
            kind: 'error',
            message: t('pages.products.bulkCreateCsvRowsInvalid', { rows: [...invalidRows].join(', ') }),
        };
        return;
    }

    if (!importedItems.length) {
        csvFeedback.value = { kind: 'error', message: t('pages.products.bulkCreateCsvHeaderError') };
        return;
    }

    let firstImportedIndex: number;
    if (form.items.length === 1 && isPristineBulkItem(form.items[0])) {
        form.items = importedItems;
        firstImportedIndex = 0;
    } else {
        firstImportedIndex = form.items.length;
        form.items = form.items.concat(importedItems);
    }

    if (selectionExplicit.value) {
        const next = new Set(selectedRows.value);
        importedItems.forEach((_, index) => next.add(firstImportedIndex + index));
        selectedRows.value = next;
    }

    selectedIndex.value = firstImportedIndex;
    sidebarTab.value = 'details';
    clearFilters();
    csvFeedback.value = {
        kind: 'success',
        message: t('pages.products.bulkCreateCsvImported', { count: importedItems.length }),
    };
}

async function onCsvSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (file) {
        await importCsvFile(file);
    }
}

async function onCsvDropped(event: DragEvent) {
    const file = event.dataTransfer?.files[0];
    if (file) {
        await importCsvFile(file);
    }
}

function fieldError(key: keyof ProductBulkCreateFormData): string | undefined {
    return form.errors[key];
}

function itemError(index: number, key: string): string | undefined {
    const submittedIndex = submittedRows.value.findIndex((row) => row === form.items[index]);
    if (submittedRows.value.length && submittedIndex < 0) {
        return undefined;
    }

    const errorIndex = submittedIndex >= 0 ? submittedIndex : index;
    return (form.errors as Record<string, string | undefined>)[`items.${errorIndex}.${key}`];
}

function rowsToSubmit(): ProductBulkItemRow[] {
    const indices = selectionExplicit.value
        ? [...selectedRows.value].filter((index) => filledRowIndexSet.value.has(index))
        : filledRowIndices.value;

    return indices.map((index) => form.items[index]).filter(Boolean);
}

async function submitForm() {
    if (!globalsReady.value) {
        return;
    }

    const rows = rowsToSubmit();
    if (!rows.length) {
        return;
    }

    const confirmed = await confirmSave(t('common.create'), t('entities.products'));
    if (!confirmed) {
        return;
    }

    submittedRows.value = rows;
    form.transform((data) => ({
        ...data,
        items: rows.map((row) => serializeBulkItem(row)),
    }));

    form.post(route('admin.products.bulk-store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <form class="bulk-create-page" @submit.prevent="submitForm">
            <header class="bulk-create-header">
                <nav class="bulk-create-breadcrumb" aria-label="Breadcrumb">
                    <Link :href="route('admin.products.index')" class="bulk-create-breadcrumb__link">
                        {{ page.header }}
                    </Link>
                    <span class="bulk-create-breadcrumb__sep">›</span>
                    <span class="bulk-create-breadcrumb__current">{{ pageTitle }}</span>
                </nav>
                <h1 class="bulk-create-title">{{ pageTitle }}</h1>
                <p class="bulk-create-subtitle">{{ t('pages.products.bulkCreateDescription') }}</p>
            </header>

            <div class="bulk-create-method-tabs" role="tablist">
                <button
                    type="button"
                    role="tab"
                    class="bulk-create-method-tabs__tab"
                    :class="{ 'bulk-create-method-tabs__tab--active': inputMethod === 'csv' }"
                    @click="inputMethod = 'csv'"
                >
                    {{ t('pages.products.bulkCreateTabCsv') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    class="bulk-create-method-tabs__tab"
                    :class="{ 'bulk-create-method-tabs__tab--active': inputMethod === 'bulk-form' }"
                    @click="inputMethod = 'bulk-form'"
                >
                    {{ t('pages.products.bulkCreateTabForm') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    class="bulk-create-method-tabs__tab"
                    :class="{ 'bulk-create-method-tabs__tab--active': inputMethod === 'manual' }"
                    @click="inputMethod = 'manual'"
                >
                    {{ t('pages.products.bulkCreateTabManual') }}
                </button>
            </div>

            <section class="bulk-create-setup" aria-labelledby="bulk-create-setup-title">
                <div class="bulk-create-setup__intro">
                    <h2 id="bulk-create-setup-title" class="bulk-create-setup__title">
                        {{ t('pages.products.bulkCreateMoreSettings') }}
                    </h2>
                    <p class="bulk-create-setup__hint">{{ t('pages.products.bulkCreateDescription') }}</p>
                </div>
                <div class="bulk-create-setup__fields">
                    <FormField label-key="company" required :error="fieldError('company_id')">
                        <Select v-model="form.company_id">
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ companyLabel(company) }}
                            </option>
                        </Select>
                    </FormField>
                    <FormField label-key="salesPlan" required :error="fieldError('sales_plan_id')">
                        <Select v-model="form.sales_plan_id">
                            <option value="">{{ t('fields.selectSalesPlan') }}</option>
                            <option v-for="plan in filteredSalesPlans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} ({{ plan.plan_code }})
                            </option>
                        </Select>
                    </FormField>
                </div>
            </section>

            <div class="bulk-create-workspace">
                <section class="bulk-create-main">
                    <div v-if="inputMethod === 'csv'" class="flex flex-col gap-3">
                        <div class="bulk-create-upload-grid">
                            <button
                                type="button"
                                class="bulk-create-dropzone"
                                :disabled="catalogOptionsLoading"
                                @click="openCsvPicker"
                                @dragover.prevent
                                @drop.prevent="onCsvDropped"
                            >
                                <span class="bulk-create-dropzone__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0L9.75 12M12 9.75l2.25 2.25M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V8.25a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </span>
                                <span class="bulk-create-dropzone__title">{{ t('pages.products.bulkCreateDropTitle') }}</span>
                                <span class="bulk-create-dropzone__hint">{{ t('pages.products.bulkCreateDropHint') }}</span>
                            </button>
                            <input ref="csvInputRef" type="file" accept=".csv,text/csv" class="hidden" @change="onCsvSelected" />

                            <aside class="bulk-create-csv-requirements">
                                <h3 class="bulk-create-csv-requirements__title">{{ t('pages.products.bulkCreateCsvRequirements') }}</h3>
                                <p class="my-3 text-xs text-ink-muted">{{ t('pages.products.bulkCreateCsvHelp') }}</p>
                                <Button type="button" variant="secondary" class="w-full" @click="downloadCsvSample">
                                    {{ t('pages.products.bulkCreateDownloadSample') }}
                                </Button>
                            </aside>
                        </div>
                        <p
                            v-if="csvFeedback"
                            class="px-1"
                            :class="csvFeedback.kind === 'error' ? 'form-error' : 'text-sm text-emerald-700'"
                            :role="csvFeedback.kind === 'error' ? 'alert' : 'status'"
                        >
                            {{ csvFeedback.message }}
                        </p>
                    </div>

                    <div v-else-if="inputMethod === 'bulk-form'" class="bulk-create-form-note">
                        <p>{{ hint('bulkCreateProducts') }}</p>
                        <p class="mt-1">{{ hint('bulkCreateProductsEditLater') }}</p>
                    </div>

                    <div v-else class="bulk-create-form-note">
                        <p>{{ t('pages.products.bulkCreateManualHint') }}</p>
                    </div>

                    <section class="bulk-create-preview">
                        <div class="bulk-create-preview__header">
                            <h2 class="bulk-create-preview__title">
                                {{ t('pages.products.bulkCreatePreviewTitle') }}
                                <span class="bulk-create-preview__count">({{ filledRowIndices.length }} {{ t('entities.products').toLowerCase() }})</span>
                            </h2>
                        </div>

                        <div class="bulk-create-preview__toolbar">
                            <div class="bulk-create-preview__filters">
                                <Input
                                    v-model="searchQuery"
                                    class="bulk-create-preview__search"
                                    :placeholder="t('pages.products.bulkCreateSearchPlaceholder')"
                                />
                                <Select v-model="filterCategory" class="bulk-create-preview__filter">
                                    <option value="">{{ t('pages.products.bulkCreateAllCategories') }}</option>
                                    <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </Select>
                                <Select v-model="filterStatus" class="bulk-create-preview__filter">
                                    <option value="">{{ t('pages.products.bulkCreateAllStatus') }}</option>
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </div>
                            <div class="bulk-create-preview__toolbar-actions">
                                <Button type="button" variant="ghost" @click="clearFilters">
                                    {{ t('pages.products.bulkCreateClearFilter') }}
                                </Button>
                            </div>
                        </div>

                        <p v-if="form.errors.items" class="form-error px-1">{{ form.errors.items }}</p>

                        <div class="bulk-create-preview-table-wrap scrollbar-visible" :class="{ 'bulk-create-preview-table-wrap--locked': !globalsReady }">
                            <table class="bulk-create-preview-table">
                                <thead>
                                    <tr>
                                        <th class="bulk-create-preview-table__number">#</th>
                                        <th class="bulk-create-preview-table__check">
                                            <input
                                                type="checkbox"
                                                :checked="allVisibleSelected"
                                                :disabled="!globalsReady || !visibleFilledRows.length"
                                                @change="toggleSelectAllVisible"
                                            />
                                        </th>
                                        <th class="bulk-create-preview-table__image">{{ t('fields.image') }}</th>
                                        <th>{{ t('fields.name') }}</th>
                                        <th>{{ t('fields.sku') }}</th>
                                        <th>{{ t('fields.productType') }}</th>
                                        <th>{{ t('fields.category') }}</th>
                                        <th>{{ t('fields.brand') }}</th>
                                        <th>{{ t('fields.unit') }}</th>
                                        <th>{{ t('fields.cost') }}</th>
                                        <th>{{ t('fields.tax') }}</th>
                                        <th>{{ t('fields.basePrice') }}</th>
                                        <th>{{ t('fields.stock') }}</th>
                                        <th>Description</th>
                                        <th>Related data</th>
                                        <th class="bulk-create-preview-table__status">{{ t('fields.status') }}</th>
                                        <th class="bulk-create-preview-table__actions">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="{ item, index } in paginatedRows"
                                        :key="index"
                                        class="bulk-create-preview-table__row"
                                        :class="{ 'bulk-create-preview-table__row--selected': isRowSelected(index) }"
                                        @click="selectRow(index)"
                                    >
                                        <td class="bulk-create-preview-table__number">{{ index + 1 }}</td>
                                        <td class="bulk-create-preview-table__check" @click.stop>
                                            <input
                                                type="checkbox"
                                                :checked="isRowSelected(index)"
                                                :disabled="!globalsReady || !filledRowIndexSet.has(index)"
                                                @change="toggleRowSelection(index)"
                                            />
                                        </td>
                                        <td class="bulk-create-preview-table__image">
                                            <div class="bulk-create-preview-table__thumb">
                                                <img v-if="item.image_preview" :src="item.image_preview" alt="" />
                                                <span v-else>{{ item.name.trim().charAt(0) || '?' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="bulk-create-preview-table__name">{{ item.name || '—' }}</span>
                                            <p v-if="itemError(index, 'name')" class="form-error">{{ itemError(index, 'name') }}</p>
                                        </td>
                                        <td>
                                            <span>{{ item.sku || '—' }}</span>
                                            <p v-if="itemError(index, 'sku')" class="form-error">{{ itemError(index, 'sku') }}</p>
                                        </td>
                                        <td>{{ form.product_type }}</td>
                                        <td>{{ categoryName(item.category_id) }}</td>
                                        <td>{{ brandName(item.brand_id) }}</td>
                                        <td>{{ unitLabel(item.unit_id) }}</td>
                                        <td>{{ formatMoney(item.cost) }}</td>
                                        <td>{{ taxName(form.tax_id) }}</td>
                                        <td>{{ formatMoney(item.base_price) }}</td>
                                        <td>{{ stockLabel(item) }}</td>
                                        <td class="max-w-[16rem] truncate" :title="item.description || undefined">{{ item.description || '—' }}</td>
                                        <td class="bulk-create-preview-table__related">
                                            <div class="bulk-create-related-links">
                                                <button v-if="item.variants.length" type="button" class="bulk-create-table-link" @click.stop="openRelationModal('variants', index)">{{ item.variants.length }} variant{{ item.variants.length === 1 ? '' : 's' }}</button>
                                                <button v-if="item.modifier_groups.length" type="button" class="bulk-create-table-link" @click.stop="openRelationModal('modifiers', index)">{{ item.modifier_groups.length }} modifier group{{ item.modifier_groups.length === 1 ? '' : 's' }}</button>
                                                <button v-if="item.components.length" type="button" class="bulk-create-table-link" @click.stop="openRelationModal('components', index)">{{ item.components.length }} component{{ item.components.length === 1 ? '' : 's' }}</button>
                                                <button v-if="item.ingredients.length" type="button" class="bulk-create-table-link" @click.stop="openRelationModal('ingredients', index)">{{ item.ingredients.length }} ingredient{{ item.ingredients.length === 1 ? '' : 's' }}</button>
                                                <span v-if="!item.variants.length && !item.modifier_groups.length && !item.components.length && !item.ingredients.length">—</span>
                                            </div>
                                        </td>
                                        <td class="bulk-create-preview-table__status">
                                            <Badge :variant="form.status === 'active' ? 'success' : 'neutral'">
                                                {{ form.status === 'active' ? t('common.active') : t('common.inactive') }}
                                            </Badge>
                                        </td>
                                        <td class="bulk-create-preview-table__actions" @click.stop>
                                            <button type="button" class="bulk-create-icon-btn" :title="t('common.edit')" @click="selectRow(index)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="bulk-create-icon-btn bulk-create-icon-btn--danger" :title="t('common.delete')" @click="removeRow(index)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.916m7.5 0H8.625" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <TablePagination
                            class="bulk-create-preview__pagination"
                            :total="filteredRows.length"
                            :page="tablePage"
                            :page-size="tablePageSize"
                            :page-size-options="[10, 20, 50]"
                            @update:page="tablePage = $event"
                            @update:page-size="tablePageSize = $event"
                        />
                    </section>

                    <footer class="bulk-create-footer">
                        <div class="bulk-create-footer__meta">
                            <span>{{ t('pages.products.bulkCreateSelectedCount', { count: selectedCount }) }}</span>
                            <button type="button" class="bulk-create-footer__clear" @click="clearSelection">
                                {{ t('pages.products.bulkCreateClearSelection') }}
                            </button>
                        </div>
                        <div class="bulk-create-footer__actions">
                            <Button type="button" variant="secondary" :disabled="!globalsReady" @click="addToList">
                                {{ t('pages.products.bulkCreateAddToList') }}
                            </Button>
                            <Button type="submit" :disabled="!globalsReady || form.processing || selectedCount === 0">
                                {{ form.processing ? t('common.saving') : createButtonLabel }}
                            </Button>
                        </div>
                    </footer>
                </section>

                <aside class="bulk-create-sidebar">
                    <div class="bulk-create-sidebar__tabs" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            class="bulk-create-sidebar__tab"
                            :class="{ 'bulk-create-sidebar__tab--active': sidebarTab === 'details' }"
                            @click="sidebarTab = 'details'"
                        >
                            {{ t('pages.products.bulkCreateSidebarDetails') }}
                        </button>
                        <button
                            v-if="selectedItem.has_variants"
                            type="button"
                            role="tab"
                            class="bulk-create-sidebar__tab"
                            :class="{ 'bulk-create-sidebar__tab--active': sidebarTab === 'variants' }"
                            @click="sidebarTab = 'variants'"
                        >
                            Product Variants
                        </button>
                        <button
                            v-if="form.product_type === 'menu_item' && selectedItem.has_modifiers"
                            type="button"
                            role="tab"
                            class="bulk-create-sidebar__tab"
                            :class="{ 'bulk-create-sidebar__tab--active': sidebarTab === 'modifiers' }"
                            @click="sidebarTab = 'modifiers'"
                        >
                            Modifiers
                        </button>
                        <button
                            v-if="form.product_type === 'menu_item' && selectedItem.has_components"
                            type="button"
                            role="tab"
                            class="bulk-create-sidebar__tab"
                            :class="{ 'bulk-create-sidebar__tab--active': sidebarTab === 'components' }"
                            @click="sidebarTab = 'components'"
                        >
                            Product Components
                        </button>
                    </div>

                    <div v-if="sidebarTab === 'details'" class="bulk-create-sidebar__body scrollbar-visible">
                        <div class="bulk-create-image-picker">
                            <div class="bulk-create-image-main">
                                <img v-if="selectedItem.image_preview" :src="selectedItem.image_preview" alt="" />
                                <div v-else class="bulk-create-image-main__placeholder">
                                    <span>{{ selectedItem.name.trim().charAt(0) || '?' }}</span>
                                </div>
                                <span class="bulk-create-image-main__badge">Main Image</span>
                                <button v-if="selectedItem.image_preview" type="button" class="bulk-create-image-main__remove" title="Remove all images" @click="removeImage">
                                    ×
                                </button>
                            </div>
                            <div class="bulk-create-image-gallery">
                                <button
                                    v-for="(image, imageIndex) in selectedItem.images"
                                    :key="image.client_key"
                                    type="button"
                                    class="bulk-create-image-thumb"
                                    :class="{ 'bulk-create-image-thumb--default': image.is_default }"
                                    :title="image.is_default ? 'Main image' : 'Set as main image'"
                                    @click="setMainImage(imageIndex)"
                                >
                                    <img :src="image.preview_url || image.url" :alt="image.name || 'Product image'" />
                                    <span v-if="image.is_default" class="bulk-create-image-thumb__badge">Main</span>
                                    <span class="bulk-create-image-thumb__remove" title="Remove image" @click.stop="removeImageAt(imageIndex)">×</span>
                                </button>
                                <button
                                    v-if="selectedItem.images.length < 10"
                                    type="button"
                                    class="bulk-create-image-add"
                                    title="Add product images"
                                    @click="openImagePicker"
                                >
                                    +
                                </button>
                            </div>
                            <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" @change="onImageSelected" />
                        </div>
                        <p class="bulk-create-image-hint">{{ selectedItem.images.length }}/10 images · 1 MB max each</p>

                        <div class="bulk-create-sidebar-fields">
                            <FormField label-key="name" required :error="itemError(selectedIndex, 'name')">
                                <Input v-model="selectedItem.name" :disabled="!globalsReady" />
                            </FormField>
                            <FormField label-key="sku" required :error="itemError(selectedIndex, 'sku')">
                                <Input v-model="selectedItem.sku" :disabled="!globalsReady" />
                            </FormField>
                            <FormField label-key="category" :error="itemError(selectedIndex, 'category_id')">
                                <Select v-model="selectedItem.category_id" :disabled="!globalsReady">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="brand" :error="itemError(selectedIndex, 'brand_id')">
                                <Select v-model="selectedItem.brand_id" :disabled="!globalsReady">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="brand in filteredBrands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="productType" :error="fieldError('product_type')">
                                <Select v-model="form.product_type" :disabled="!globalsReady">
                                    <option value="retail">{{ t('messages.productTypeRetail') }}</option>
                                    <option value="menu_item">{{ t('messages.productTypeMenuItem') }}</option>
                                    <option value="ingredient">{{ t('messages.productTypeIngredient') }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="barcode" :error="itemError(selectedIndex, 'barcode')">
                                <Input v-model="selectedItem.barcode" :disabled="!globalsReady" />
                            </FormField>
                        </div>

                        <section class="bulk-create-sidebar-section">
                            <h3 class="bulk-create-sidebar-section__title">{{ t('fields.price') }}</h3>
                            <div class="bulk-create-sidebar-grid bulk-create-sidebar-grid--price">
                                <FormField label-key="cost" :error="itemError(selectedIndex, 'cost')">
                                    <Input v-model="selectedItem.cost" type="number" min="0" step="0.01" :disabled="!globalsReady" />
                                </FormField>
                                <FormField :label="t('fields.basePrice')" :error="itemError(selectedIndex, 'base_price')">
                                    <Input v-model="selectedItem.base_price" type="number" min="0" step="0.01" :disabled="!globalsReady" />
                                </FormField>
                                <FormField label-key="tax" :error="fieldError('tax_id')">
                                    <Select v-model="form.tax_id" :disabled="!globalsReady">
                                        <option value="">{{ t('fields.none') }}</option>
                                        <option v-for="tax in filteredTaxes" :key="tax.id" :value="tax.id">
                                            {{ tax.name }} ({{ tax.rate }}%)
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                        </section>

                        <section class="bulk-create-sidebar-section">
                            <div class="bulk-create-sidebar-section__head">
                                <h3 class="bulk-create-sidebar-section__title">{{ t('pages.products.bulkCreateInventory') }}</h3>
                                <FormToggle v-model="form.track_inventory" label-key="trackInventory" />
                            </div>
                            <div v-if="form.track_inventory" class="bulk-create-sidebar-grid bulk-create-sidebar-grid--inventory">
                                <FormField label-key="idealQty" :error="itemError(selectedIndex, 'ideal_qty')">
                                    <Input v-model="selectedItem.ideal_qty" type="number" min="0" step="0.0001" :disabled="!globalsReady" />
                                </FormField>
                                <FormField label-key="warningQty" :error="itemError(selectedIndex, 'warning_qty')">
                                    <Input v-model="selectedItem.warning_qty" type="number" min="0" step="0.0001" :disabled="!globalsReady" />
                                </FormField>
                                <FormField label-key="unit" :error="itemError(selectedIndex, 'unit_id')">
                                    <Select v-model="selectedItem.unit_id" :disabled="!globalsReady">
                                        <option value="">{{ t('fields.none') }}</option>
                                        <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                        </section>

                        <section class="bulk-create-sidebar-section">
                            <h3 class="bulk-create-sidebar-section__title">{{ t('fields.options') }}</h3>
                            <div class="bulk-create-sidebar-fields bulk-create-sidebar-fields--options">
                                <FormToggle v-model="selectedItem.has_variants" label-key="hasVariants" :disabled="!globalsReady" />
                                <FormToggle
                                    v-if="form.product_type === 'menu_item'"
                                    v-model="selectedItem.has_modifiers"
                                    label-key="hasModifiers"
                                    :disabled="!globalsReady"
                                />
                                <FormToggle
                                    v-if="form.product_type === 'menu_item'"
                                    v-model="selectedItem.has_components"
                                    label-key="hasComponents"
                                    :disabled="!globalsReady"
                                />
                            </div>
                        </section>

                        <div class="bulk-create-sidebar-fields bulk-create-sidebar-fields--footer">
                            <FormField label-key="status" :error="fieldError('status')">
                                <Select v-model="form.status" :disabled="!globalsReady">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="description" :error="itemError(selectedIndex, 'description')">
                                <textarea
                                    v-model="selectedItem.description"
                                    class="bulk-create-textarea"
                                    rows="4"
                                    :disabled="!globalsReady"
                                />
                            </FormField>
                        </div>
                    </div>

                    <div v-else class="bulk-create-sidebar__body bulk-create-sidebar__option-body scrollbar-visible">
                        <template v-if="sidebarTab === 'variants'">
                            <div class="bulk-create-option-header">
                                <div>
                                    <h3 class="bulk-create-sidebar-section__title">Product Variants</h3>
                                    <p class="text-xs text-ink-muted">{{ t('sections.productVariants') }}</p>
                                </div>
                                <Button type="button" variant="secondary" class="!px-2 !py-1 text-xs" :disabled="!globalsReady" @click="addVariant">
                                    {{ t('forms.actions.addVariant') }}
                                </Button>
                            </div>
                            <div v-for="(variant, variantIndex) in selectedItem.variants" :key="variant.client_key" class="bulk-create-variant-row">
                                <FormField label-key="variantCode"><Input v-model="variant.variant_code" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="name"><Input v-model="variant.name" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="sku"><Input v-model="variant.sku" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="sellingPrice"><Input v-model="variant.selling_price" type="number" min="0" step="0.01" :disabled="!globalsReady" /></FormField>
                                <Button type="button" variant="ghost" class="bulk-create-variant-row__remove" @click="removeVariant(variantIndex)">
                                    {{ t('forms.actions.removeLine') }}
                                </Button>
                            </div>
                        </template>
                        <template v-else-if="sidebarTab === 'modifiers'">
                            <h3 class="bulk-create-sidebar-section__title">
                                Modifiers
                            </h3>
                            <div class="bulk-create-option-header">
                                <p class="text-xs text-ink-muted">{{ t('sections.modifierGroups') }}</p>
                                <Button type="button" variant="secondary" class="!px-2 !py-1 text-xs" @click="addModifierGroup">Add group</Button>
                            </div>
                            <div v-for="(group, groupIndex) in selectedItem.modifier_groups" :key="group.client_key" class="bulk-create-option-block">
                                <div class="bulk-create-sidebar-grid">
                                    <FormField label-key="groupCode"><Input v-model="group.group_code" :disabled="!globalsReady" /></FormField>
                                    <FormField label-key="name"><Input v-model="group.name" :disabled="!globalsReady" /></FormField>
                                    <FormField label-key="selectionType"><Select v-model="group.selection_type" :disabled="!globalsReady"><option value="single">Single</option><option value="multiple">Multiple</option></Select></FormField>
                                    <FormToggle v-model="group.is_required" label-key="requiredGroup" :disabled="!globalsReady" />
                                </div>
                                <div class="bulk-create-option-header">
                                    <span class="text-xs font-medium text-ink">Options</span>
                                    <Button type="button" variant="ghost" class="!px-2 !py-1 text-xs" @click="addModifierOption(groupIndex)">Add option</Button>
                                </div>
                                <div v-for="(option, optionIndex) in group.options" :key="option.client_key" class="bulk-create-option-row">
                                    <Input v-model="option.option_code" placeholder="Code" :disabled="!globalsReady" />
                                    <Input v-model="option.name" placeholder="Name" :disabled="!globalsReady" />
                                    <Input v-model="option.price_adjustment" type="number" step="0.01" placeholder="Price" :disabled="!globalsReady" />
                                    <button type="button" class="bulk-create-footer__clear" @click="removeModifierOption(groupIndex, optionIndex)">Remove</button>
                                </div>
                                <Button type="button" variant="ghost" class="text-xs" @click="removeModifierGroup(groupIndex)">Remove group</Button>
                            </div>
                        </template>
                        <template v-else>
                            <h3 class="bulk-create-sidebar-section__title">Product Components</h3>
                            <div class="bulk-create-option-header">
                                <p class="text-xs text-ink-muted">{{ t('sections.productComponents') }}</p>
                                <Button type="button" variant="secondary" class="!px-2 !py-1 text-xs" @click="addComponent">Add component</Button>
                            </div>
                            <div v-for="(component, componentIndex) in selectedItem.components" :key="componentIndex" class="bulk-create-option-row">
                                <FormField label-key="componentProduct"><Select v-model="component.component_product_id" :disabled="!globalsReady"><option value="">Select product</option><option v-for="product in filteredComponentProducts" :key="product.id" :value="product.id">{{ product.name }} ({{ product.sku }})</option></Select></FormField>
                                <FormField label-key="quantity"><Input v-model="component.quantity" type="number" min="0.0001" step="0.0001" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="unit"><Select v-model="component.unit_id" :disabled="!globalsReady"><option value="">Default unit</option><option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">{{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}</option></Select></FormField>
                                <FormField label-key="sortOrder"><Input v-model="component.sort_order" type="number" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="notes"><Input v-model="component.notes" :disabled="!globalsReady" /></FormField>
                                <FormToggle v-model="component.is_optional" label-key="optionalComponent" :disabled="!globalsReady" />
                                <button type="button" class="bulk-create-footer__clear" @click="removeComponent(componentIndex)">Remove component</button>
                            </div>
                            <h3 class="bulk-create-sidebar-section__title mt-6">Ingredients</h3>
                            <div class="bulk-create-option-header">
                                <p class="text-xs text-ink-muted">{{ t('sections.recipeIngredients') }}</p>
                                <Button type="button" variant="secondary" class="!px-2 !py-1 text-xs" @click="addIngredient">Add ingredient</Button>
                            </div>
                            <div v-for="(ingredient, ingredientIndex) in selectedItem.ingredients" :key="ingredientIndex" class="bulk-create-option-row">
                                <FormField label-key="product"><Select v-model="ingredient.ingredient_product_id" :disabled="!globalsReady"><option value="">Select ingredient</option><option v-for="product in filteredIngredientProducts" :key="product.id" :value="product.id">{{ product.name }} ({{ product.sku }})</option></Select></FormField>
                                <FormField label-key="quantity"><Input v-model="ingredient.quantity" type="number" min="0.0001" step="0.0001" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="unit"><Select v-model="ingredient.unit_id" :disabled="!globalsReady"><option value="">Default unit</option><option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">{{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}</option></Select></FormField>
                                <FormField label-key="sortOrder"><Input v-model="ingredient.sort_order" type="number" :disabled="!globalsReady" /></FormField>
                                <FormField label-key="notes"><Input v-model="ingredient.notes" :disabled="!globalsReady" /></FormField>
                                <FormToggle v-model="ingredient.is_optional" label-key="optionalIngredient" :disabled="!globalsReady" />
                                <button type="button" class="bulk-create-footer__clear" @click="removeIngredient(ingredientIndex)">Remove ingredient</button>
                            </div>
                        </template>
                    </div>

                </aside>
            </div>
        </form>

        <Modal :show="relationModal !== null" max-width="lg" @close="relationModal = null">
            <template #header>
                <h3 class="font-display text-lg font-semibold text-ink">
                    {{ relationModal?.type === 'variants' ? 'Product Variants' : relationModal?.type === 'modifiers' ? 'Modifiers' : relationModal?.type === 'components' ? 'Product Components' : 'Ingredients' }}
                </h3>
            </template>
            <div class="space-y-3">
                <div v-for="(row, rowIndex) in relationRows()" :key="rowIndex" class="bulk-create-relation-modal__row">
                    <template v-if="relationModal?.type === 'variants'">
                        <strong>{{ row.name || row.variant_code }}</strong><span>{{ row.sku || 'No SKU' }}</span><span>{{ formatMoney(row.selling_price) }}</span>
                    </template>
                    <template v-else-if="relationModal?.type === 'modifiers'">
                        <strong>{{ row.name }}</strong><span>{{ row.group_code }}</span><span>{{ row.options?.length || 0 }} options</span>
                    </template>
                    <template v-else-if="relationModal?.type === 'components'">
                        <strong>{{ props.componentProducts.find((product) => product.id === row.component_product_id)?.name || 'Component' }}</strong><span>Qty {{ row.quantity }}</span><span>{{ unitLabel(row.unit_id) }}</span>
                    </template>
                    <template v-else>
                        <strong>{{ props.ingredientProducts.find((product) => product.id === row.ingredient_product_id)?.name || 'Ingredient' }}</strong><span>Qty {{ row.quantity }}</span><span>{{ unitLabel(row.unit_id) }}</span>
                    </template>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>
