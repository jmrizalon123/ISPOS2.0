<script setup lang="ts">
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import type { DataTableColumn } from '@/Components/ui/DataTable.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type StoreOption = { id: string; store_name: string; store_code: string; company_id: string; store_type?: string };
type ProductOption = { id: string; sku: string; name: string; company_id: string; image?: string | null; barcodes: string[] };
type InventoryOption = { store_id: string; product_id: string; qty: string };
type TransferLine = { product_id: string; name: string; sku: string; image?: string | null; quantity: string };

const page = useModulePage('transfers');
const pageTitle = useFormPageTitle('transfer', false);
const { t, column, placeholder, submit } = useLocale();
const lineColumns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: column('product') },
    { key: 'sku', label: column('sku'), class: 'hidden md:table-cell' },
    { key: 'on_hand', label: column('onHand'), class: 'text-right' },
    { key: 'quantity', label: t('fields.requestedQty'), class: 'w-36' },
]);

const props = defineProps<{
    stores: StoreOption[];
    warehouses: StoreOption[];
    products: ProductOption[];
    inventories: InventoryOption[];
    transferTypes: string[];
    priorities: string[];
}>();

const productSearch = ref('');
const barcodeQuery = ref('');
const lookupMessage = ref('');
const lookupMessageVariant = ref<'success' | 'warning' | 'danger'>('success');
const searchFocused = ref(false);
const transferLines = ref<TransferLine[]>([]);
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    transfer_type: 'store_to_store',
    priority: 'normal',
    from_store_id: '',
    to_store_id: '',
    from_warehouse_id: '',
    to_warehouse_id: '',
    transfer_date: today,
    requested_date: today,
    expected_date: '',
    reason: '',
    reference_no: '',
    notes: '',
    lines: [] as Array<{ product_id: string; quantity: string }>,
});

const usesFromStore = computed(() => ['store_to_store', 'store_to_warehouse'].includes(form.transfer_type));
const usesToStore = computed(() => ['store_to_store', 'warehouse_to_store'].includes(form.transfer_type));
const usesFromWarehouse = computed(() => ['warehouse_to_store', 'warehouse_to_warehouse'].includes(form.transfer_type));
const usesToWarehouse = computed(() => ['store_to_warehouse', 'warehouse_to_warehouse'].includes(form.transfer_type));

const sourceLocationId = computed(() =>
    usesFromWarehouse.value ? form.from_warehouse_id : form.from_store_id,
);

const sourceLocation = computed(() => {
    const list = usesFromWarehouse.value ? props.warehouses : props.stores;
    return list.find((store) => store.id === sourceLocationId.value) ?? null;
});

const destinationStores = computed(() =>
    props.stores.filter((store) => {
        if (sourceLocation.value && store.company_id !== sourceLocation.value.company_id) {
            return false;
        }
        return store.id !== sourceLocationId.value;
    }),
);

const destinationWarehouses = computed(() =>
    props.warehouses.filter((store) => {
        if (sourceLocation.value && store.company_id !== sourceLocation.value.company_id) {
            return false;
        }
        return store.id !== sourceLocationId.value;
    }),
);

const companyProducts = computed(() => {
    if (sourceLocation.value) {
        return props.products.filter((product) => product.company_id === sourceLocation.value!.company_id);
    }

    const companyIds = [...new Set([...props.stores, ...props.warehouses].map((store) => store.company_id).filter(Boolean))];
    if (companyIds.length === 1) {
        return props.products.filter((product) => product.company_id === companyIds[0]);
    }

    return props.products;
});

const addedProductIds = computed(() => new Set(transferLines.value.map((line) => line.product_id)));

const searchResults = computed(() => {
    const term = productSearch.value.trim().toLowerCase();
    if (term.length < 2) {
        return [] as ProductOption[];
    }

    return companyProducts.value
        .filter((product) => {
            if (addedProductIds.value.has(product.id)) {
                return false;
            }

            return (
                product.name.toLowerCase().includes(term)
                || product.sku.toLowerCase().includes(term)
                || product.barcodes.some((barcode) => barcode.toLowerCase().includes(term))
            );
        })
        .slice(0, 8);
});

const canLookupProducts = computed(() => !!sourceLocationId.value && companyProducts.value.length > 0);

const showSearchPanel = computed(
    () => searchFocused.value && productSearch.value.trim().length >= 2,
);

const totalRequestedQty = computed(() =>
    transferLines.value.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0),
);

function typeLabel(type: string): string {
    return type.replaceAll('_', ' ');
}

function availableQty(productId: string): number {
    if (!sourceLocationId.value) {
        return 0;
    }

    const row = props.inventories.find(
        (item) => item.store_id === sourceLocationId.value && item.product_id === productId,
    );

    return row ? parseFloat(row.qty) || 0 : 0;
}

function formatQty(value: number): string {
    if (Number.isInteger(value)) {
        return String(value);
    }

    return value.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
}

function stockVariant(qty: number): 'success' | 'warning' | 'danger' {
    if (qty <= 0) {
        return 'danger';
    }

    if (qty <= 5) {
        return 'warning';
    }

    return 'success';
}

function productInitial(name: string): string {
    return name.trim().charAt(0).toUpperCase() || '?';
}

function productImageUrl(image?: string | null): string | null {
    return image?.trim() || null;
}

function setLookupMessage(message: string, variant: 'success' | 'warning' | 'danger'): void {
    lookupMessage.value = message;
    lookupMessageVariant.value = variant;
}

function lineError(index: number, field: string): string | undefined {
    const errors = form.errors as Record<string, string | undefined>;

    return errors[`lines.${index}.${field}`] ?? errors[`lines.${index}.product_id`];
}

function findProduct(query: string): ProductOption | null {
    const normalized = query.trim().toLowerCase();
    if (!normalized) {
        return null;
    }

    return (
        companyProducts.value.find((product) => product.sku.toLowerCase() === normalized)
        ?? companyProducts.value.find((product) =>
            product.barcodes.some((barcode) => barcode.toLowerCase() === normalized),
        )
        ?? null
    );
}

function addProduct(product: ProductOption, initialQty = '1'): void {
    lookupMessage.value = '';

    const existing = transferLines.value.find((line) => line.product_id === product.id);
    if (existing) {
        const nextQty = Number(existing.quantity || 0) + Number(initialQty || 1);
        existing.quantity = String(nextQty);
        setLookupMessage(`${product.name} quantity updated to ${nextQty}.`, 'success');
        return;
    }

    transferLines.value.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        image: product.image,
        quantity: initialQty,
    });
    setLookupMessage(`${product.name} added to transfer.`, 'success');
}

function addFromSearch(product: ProductOption): void {
    addProduct(product);
    productSearch.value = '';
    searchFocused.value = false;
}

function handleBarcodeScan(): void {
    lookupMessage.value = '';

    if (!canLookupProducts.value) {
        setLookupMessage('Select a source location before adding products.', 'warning');
        return;
    }

    const product = findProduct(barcodeQuery.value);
    if (!product) {
        setLookupMessage(`No product found for “${barcodeQuery.value.trim()}”.`, 'danger');
        barcodeQuery.value = '';
        return;
    }

    addProduct(product);
    barcodeQuery.value = '';
}

function removeLine(index: number): void {
    transferLines.value.splice(index, 1);
}

function blurSearch(): void {
    window.setTimeout(() => {
        searchFocused.value = false;
    }, 150);
}

watch(
    () => form.transfer_type,
    () => {
        if (!usesFromStore.value) form.from_store_id = '';
        if (!usesToStore.value) form.to_store_id = '';
        if (!usesFromWarehouse.value) form.from_warehouse_id = '';
        if (!usesToWarehouse.value) form.to_warehouse_id = '';
    },
);

watch(sourceLocationId, () => {
    transferLines.value = [];
    productSearch.value = '';
    barcodeQuery.value = '';
    lookupMessage.value = '';
});

function submitForm() {
    form.lines = transferLines.value
        .filter((line) => Number(line.quantity) > 0)
        .map((line) => ({ product_id: line.product_id, quantity: line.quantity }));

    form.post(route('admin.inventory.transfers.store'));
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="transfers"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.inventory.transfers.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-0 px-3 py-4 sm:px-4 sm:py-5 md:px-5 lg:px-6">
                    <FormSection title-key="transferSetup" description-key="transferSetup">
                        <div class="form-grid lg:grid-cols-4">
                            <FormField label-key="transferType" required :error="form.errors.transfer_type">
                                <Select v-model="form.transfer_type" required>
                                    <option v-for="type in transferTypes" :key="type" :value="type">
                                        {{ typeLabel(type) }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="priority" required :error="form.errors.priority">
                                <Select v-model="form.priority" required>
                                    <option v-for="level in priorities" :key="level" :value="level">
                                        {{ level }}
                                    </option>
                                </Select>
                            </FormField>
                            <div class="lg:col-span-2">
                                <FormField label-key="reference" :error="form.errors.reference_no">
                                    <Input v-model="form.reference_no" />
                                </FormField>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto_1fr] md:items-end">
                            <div class="space-y-3">
                                <FormField
                                    v-if="usesFromStore"
                                    label-key="fromStore"
                                    required
                                    :error="form.errors.from_store_id"
                                >
                                    <Select v-model="form.from_store_id" required>
                                        <option value="" disabled>{{ t('fields.selectStore') }}</option>
                                        <option v-for="store in stores" :key="store.id" :value="store.id">
                                            {{ store.store_name }} ({{ store.store_code }})
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField
                                    v-if="usesFromWarehouse"
                                    label-key="fromWarehouse"
                                    required
                                    :error="form.errors.from_warehouse_id"
                                >
                                    <Select v-model="form.from_warehouse_id" required>
                                        <option value="" disabled>Select source warehouse</option>
                                        <option v-for="store in warehouses" :key="store.id" :value="store.id">
                                            {{ store.store_name }} ({{ store.store_code }})
                                        </option>
                                    </Select>
                                </FormField>
                            </div>

                            <div class="hidden justify-center pb-2 text-ink-muted md:flex" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>

                            <div class="space-y-3">
                                <FormField
                                    v-if="usesToStore"
                                    label-key="toStore"
                                    required
                                    :error="form.errors.to_store_id"
                                >
                                    <Select v-model="form.to_store_id" required>
                                        <option value="" disabled>{{ t('fields.selectStore') }}</option>
                                        <option v-for="store in destinationStores" :key="store.id" :value="store.id">
                                            {{ store.store_name }} ({{ store.store_code }})
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField
                                    v-if="usesToWarehouse"
                                    label-key="toWarehouse"
                                    required
                                    :error="form.errors.to_warehouse_id"
                                >
                                    <Select v-model="form.to_warehouse_id" required>
                                        <option value="" disabled>Select destination warehouse</option>
                                        <option v-for="store in destinationWarehouses" :key="store.id" :value="store.id">
                                            {{ store.store_name }} ({{ store.store_code }})
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection title-key="transferSetup">
                        <div class="form-grid-3">
                            <FormField label-key="transferDate" :error="form.errors.transfer_date">
                                <Input v-model="form.transfer_date" type="date" />
                            </FormField>
                            <FormField label-key="orderDate">
                                <Input v-model="form.requested_date" type="date" />
                            </FormField>
                            <FormField label-key="expectedDate">
                                <Input v-model="form.expected_date" type="date" />
                            </FormField>
                            <div class="md:col-span-2 lg:col-span-3">
                                <FormField label-key="reason">
                                    <Input v-model="form.reason" :placeholder="placeholder('adjustmentReason')" />
                                </FormField>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <FormField label-key="notes">
                                    <FormTextarea v-model="form.notes" :rows="2" :placeholder="placeholder('additionalDetails')" />
                                </FormField>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection title-key="transferLines">
                        <EmptyState
                            v-if="!sourceLocationId"
                            title="Choose a source location"
                            description="Select where stock is coming from, then search or scan products to build this transfer."
                        />

                        <template v-else>
                            <div class="mb-5 rounded-xl border border-line bg-surface-muted/25 p-3 sm:p-4">
                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                                    <div class="relative">
                                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-ink-muted">
                                            Search product
                                        </label>
                                        <div class="relative [&_input]:h-11 [&_input]:pl-10 [&_input]:text-sm">
                                            <svg
                                                class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-ink-muted"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                                            </svg>
                                            <Input
                                                v-model="productSearch"
                                                placeholder="Product name, SKU, or barcode…"
                                                :disabled="!canLookupProducts"
                                                @focus="searchFocused = true"
                                                @blur="blurSearch"
                                            />
                                        </div>
                                        <p class="mt-1.5 text-xs text-ink-muted">Type at least 2 characters to search.</p>

                                        <div
                                            v-if="showSearchPanel"
                                            class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-line bg-surface shadow-panel"
                                        >
                                            <div class="border-b border-line bg-surface-muted/60 px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
                                                {{ searchResults.length ? `${searchResults.length} result${searchResults.length === 1 ? '' : 's'}` : 'No results' }}
                                            </div>

                                            <ul v-if="searchResults.length" class="max-h-64 divide-y divide-line-subtle overflow-auto">
                                                <li v-for="product in searchResults" :key="product.id">
                                                    <button
                                                        type="button"
                                                        class="group flex w-full items-center gap-3 px-3 py-3 text-left transition hover:bg-surface-muted/70"
                                                        @mousedown.prevent="addFromSearch(product)"
                                                    >
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-surface-muted">
                                                            <img
                                                                v-if="productImageUrl(product.image)"
                                                                :src="productImageUrl(product.image)!"
                                                                :alt="product.name"
                                                                class="h-full w-full object-cover"
                                                            />
                                                            <span
                                                                v-else
                                                                class="text-sm font-semibold text-accent"
                                                            >
                                                                {{ productInitial(product.name) }}
                                                            </span>
                                                        </span>
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block truncate text-sm font-medium text-ink">{{ product.name }}</span>
                                                            <span class="mt-0.5 block truncate font-mono text-xs text-ink-muted">{{ product.sku }}</span>
                                                        </span>
                                                        <span class="flex shrink-0 flex-col items-end gap-1.5">
                                                            <Badge :variant="stockVariant(availableQty(product.id))">
                                                                {{ formatQty(availableQty(product.id)) }} on hand
                                                            </Badge>
                                                            <span class="text-[11px] font-medium text-accent opacity-0 transition group-hover:opacity-100">
                                                                Add to transfer
                                                            </span>
                                                        </span>
                                                    </button>
                                                </li>
                                            </ul>

                                            <p v-else class="px-4 py-6 text-center text-sm text-ink-muted">
                                                No products match your search.
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-ink-muted">
                                            Scan barcode
                                        </label>
                                        <div class="relative [&_input]:h-11 [&_input]:pl-10 [&_input]:font-mono [&_input]:text-sm">
                                            <svg
                                                class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-ink-muted"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2M16 5h2a1 1 0 011 1v2M16 19h2a1 1 0 001-1v-2M7 8h10v8H7z" />
                                            </svg>
                                            <Input
                                                v-model="barcodeQuery"
                                                placeholder="Scan or type barcode…"
                                                :disabled="!canLookupProducts"
                                                @keydown.enter.prevent="handleBarcodeScan"
                                            />
                                        </div>
                                        <p class="mt-1.5 text-xs text-ink-muted">Press Enter after each scan.</p>
                                    </div>
                                </div>
                            </div>

                            <Alert
                                v-if="lookupMessage"
                                :variant="lookupMessageVariant === 'success' ? 'success' : lookupMessageVariant === 'warning' ? 'warning' : 'danger'"
                                class="mb-4"
                            >
                                {{ lookupMessage }}
                            </Alert>

                            <Alert v-if="form.errors.lines" variant="danger" class="mb-4">
                                {{ form.errors.lines }}
                            </Alert>

                            <EmptyState
                                v-if="!transferLines.length"
                                title="No products added yet"
                                description="Search by name or scan a barcode to start building this transfer."
                            />

                            <div v-else class="space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 px-0.5">
                                    <p class="text-sm text-ink-muted">
                                        <span class="font-medium text-ink">{{ transferLines.length }}</span>
                                        {{ transferLines.length === 1 ? 'item' : 'items' }}
                                        ·
                                        <span class="font-mono tabular-nums text-ink">{{ formatQty(totalRequestedQty) }}</span>
                                        total qty
                                    </p>
                                </div>

                                <DataTable :columns="lineColumns" :rows="transferLines" sticky-actions viewport-fit compact>
                                    <template #cell-name="{ row }">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-surface-muted">
                                                <img
                                                    v-if="productImageUrl(row.image)"
                                                    :src="productImageUrl(row.image)!"
                                                    :alt="row.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <span
                                                    v-else
                                                    class="text-xs font-semibold text-ink-muted"
                                                >
                                                    {{ productInitial(row.name) }}
                                                </span>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-ink">{{ row.name }}</p>
                                                <p class="truncate font-mono text-xs text-ink-muted md:hidden">{{ row.sku }}</p>
                                            </div>
                                        </div>
                                    </template>
                                    <template #cell-sku="{ row }">
                                        <span class="font-mono text-xs text-ink-muted">{{ row.sku }}</span>
                                    </template>
                                    <template #cell-on_hand="{ row }">
                                        <div class="flex justify-end">
                                            <Badge :variant="stockVariant(availableQty(row.product_id))">
                                                {{ formatQty(availableQty(row.product_id)) }}
                                            </Badge>
                                        </div>
                                    </template>
                                    <template #cell-quantity="{ row, index }">
                                        <div class="max-w-[8.5rem] [&_input]:h-9 [&_input]:text-center [&_input]:font-mono [&_input]:tabular-nums">
                                            <Input
                                                v-model="row.quantity"
                                                type="number"
                                                step="0.0001"
                                                min="0.0001"
                                                required
                                            />
                                            <p v-if="lineError(index, 'quantity')" class="mt-1 text-xs text-red-500">
                                                {{ lineError(index, 'quantity') }}
                                            </p>
                                        </div>
                                    </template>
                                    <template #actions="{ index }">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="text-ink-muted hover:text-red-600 dark:hover:text-red-400"
                                            @click="removeLine(index)"
                                        >
                                            <span class="sr-only">Remove line</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </Button>
                                    </template>
                                    <template #footer>
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-ink-muted">
                                            <span>{{ transferLines.length }} line{{ transferLines.length === 1 ? '' : 's' }}</span>
                                            <span>
                                                Total requested:
                                                <span class="font-mono font-medium tabular-nums text-ink">{{ formatQty(totalRequestedQty) }}</span>
                                            </span>
                                        </div>
                                    </template>
                                </DataTable>
                            </div>
                        </template>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.inventory.transfers.index')"
                    :submit-label="submit('saveTransfer')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
