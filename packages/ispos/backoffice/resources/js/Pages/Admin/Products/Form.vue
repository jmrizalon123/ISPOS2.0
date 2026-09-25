<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormMetaRow from '@/Components/forms/FormMetaRow.vue';
import ProductImagesField from '@/Components/forms/ProductImagesField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import {
    defaultProductForm,
    emptyBarcodeRow,
    emptyComponentRow,
    emptyIngredientRow,
    emptyModifierGroupRow,
    emptyModifierOptionRow,
    emptyPriceRow,
    emptyVariantRow,
    serializeProductImages,
    type ProductFormData,
    type ProductRecord,
} from '@/types/product';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('products');
const { t, placeholder, submit, tab, tabDesc, hint } = useLocale();

const props = defineProps<{
    product: ProductRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    categories: Array<{ id: string; name: string; category_code: string; company_id: string }>;
    brands: Array<{ id: string; name: string; brand_code: string; company_id: string }>;
    units: Array<{ id: string; name: string; symbol: string | null; company_id: string }>;
    taxes: Array<{ id: string; name: string; rate: number | string; company_id: string }>;
    priceGroups: Array<{ id: string; name: string; group_code: string; is_default: boolean; company_id: string }>;
    ingredientProducts: Array<{ id: string; name: string; sku: string; company_id: string; product_type: string; sales_plan_id?: string | null }>;
    componentProducts: Array<{ id: string; name: string; sku: string; company_id: string; product_type: string; sales_plan_id?: string | null }>;
    salesPlans: Array<{ id: string; name: string; plan_code: string; company_id: string; status: string }>;
}>();

const activeTab = ref('basic');
const isEdit = computed(() => !!props.product);
const pageTitle = useFormPageTitle('product', isEdit);

const form = useForm<ProductFormData>(
    defaultProductForm(props.product, props.product?.company_id ?? props.companies[0]?.id ?? ''),
);

const isMenuItem = computed(() => form.product_type === 'menu_item');
const isIngredient = computed(() => form.product_type === 'ingredient');

const tabs = computed(() => {
    const items = [
        { key: 'basic', label: tab('basic'), description: tabDesc('basic') },
        { key: 'pricing', label: tab('pricing'), description: tabDesc('pricing') },
    ];

    if (form.track_inventory) {
        items.splice(2, 0, { key: 'inventory', label: tab('inventory'), description: tabDesc('inventory') });
    }

    if (!isIngredient.value) {
        items.push({ key: 'variants', label: tab('variants'), description: tabDesc('variants') });
    }

    if (isMenuItem.value) {
        items.push(
            { key: 'modifiers', label: tab('modifiers'), description: tabDesc('modifiers') },
            { key: 'components', label: tab('components'), description: tabDesc('components') },
            { key: 'ingredients', label: tab('ingredients'), description: tabDesc('ingredients') },
        );
    }

    items.push(
        { key: 'barcodes', label: tab('barcodes'), description: tabDesc('barcodes') },
        { key: 'status', label: tab('status'), description: tabDesc('status') },
    );

    return items;
});

const activeTabMeta = computed(() => tabs.value.find((item) => item.key === activeTab.value) ?? tabs.value[0]);

const tabErrorConfig = computed((): FormTabErrorConfig[] => {
    const configs: FormTabErrorConfig[] = [
        {
            key: 'basic',
            requiredFields: ['company_id', 'sales_plan_id', 'product_type', 'sku', 'name'],
            errorPrefixes: ['images', 'image_uploads'],
        },
        {
            key: 'pricing',
            errorPrefixes: ['cost', 'base_price', 'prices'],
        },
    ];

    if (form.track_inventory) {
        configs.push({
            key: 'inventory',
            errorPrefixes: ['ideal_qty', 'warning_qty'],
        });
    }

    if (!isIngredient.value) {
        configs.push({
            key: 'variants',
            errorPrefixes: ['variants'],
            arrayRules: [{ arrayField: 'variants', requiredKeys: ['variant_code', 'name'] }],
        });
    }

    if (isMenuItem.value) {
        configs.push(
            {
                key: 'modifiers',
                errorPrefixes: ['modifier_groups'],
                arrayRules: [
                    {
                        arrayField: 'modifier_groups',
                        requiredKeys: ['group_code', 'name'],
                        nested: { key: 'options', requiredKeys: ['option_code', 'name'] },
                    },
                ],
            },
            {
                key: 'components',
                errorPrefixes: ['components'],
                arrayRules: [{ arrayField: 'components', requiredKeys: ['component_product_id'] }],
            },
            {
                key: 'ingredients',
                errorPrefixes: ['ingredients'],
                arrayRules: [{ arrayField: 'ingredients', requiredKeys: ['ingredient_product_id'] }],
            },
        );
    }

    configs.push(
        {
            key: 'barcodes',
            errorPrefixes: ['barcodes'],
            arrayRules: [{ arrayField: 'barcodes', requiredKeys: ['barcode'] }],
        },
        {
            key: 'status',
            requiredFields: ['status'],
        },
    );

    return configs;
});

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

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
const filteredPriceGroups = computed(() =>
    props.priceGroups.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredSalesPlans = computed(() =>
    props.salesPlans.filter((item) => !form.company_id || item.company_id === form.company_id),
);
const filteredIngredientProducts = computed(() =>
    props.ingredientProducts.filter(
        (item) =>
            (!form.company_id || item.company_id === form.company_id) &&
            (!form.sales_plan_id || !item.sales_plan_id || item.sales_plan_id === form.sales_plan_id) &&
            item.id !== props.product?.id,
    ),
);
const filteredComponentProducts = computed(() =>
    props.componentProducts.filter(
        (item) =>
            (!form.company_id || item.company_id === form.company_id) &&
            (!form.sales_plan_id || !item.sales_plan_id || item.sales_plan_id === form.sales_plan_id) &&
            item.id !== props.product?.id,
    ),
);

watch(
    () => form.company_id,
    () => {
        if (form.sales_plan_id && !filteredSalesPlans.value.some((plan) => plan.id === form.sales_plan_id)) {
            form.sales_plan_id = filteredSalesPlans.value.length === 1 ? filteredSalesPlans.value[0].id : '';
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

watch(
    () => form.product_type,
    (type) => {
        if (type === 'ingredient') {
            form.has_modifiers = false;
            form.has_components = false;
            form.modifier_groups = [];
            form.components = [];
            form.ingredients = [];
        } else if (type !== 'menu_item') {
            form.has_modifiers = false;
            form.has_components = false;
            form.modifier_groups = [];
            form.components = [];
            form.ingredients = [];
        }
    },
);

watch(
    () => form.track_inventory,
    (enabled) => {
        if (!enabled) {
            if (activeTab.value === 'inventory') {
                activeTab.value = 'basic';
            }
        }
    },
);

const variantOptions = computed(() => [
    { key: '', label: t('fields.productDefault') },
    ...form.variants.map((variant) => ({
        key: variant.client_key,
        label: variant.name || variant.variant_code || variant.client_key,
    })),
]);

function companyLabel(company: (typeof props.companies)[number]): string {
    return company.display_name || company.name;
}

function addVariant() {
    form.variants.push(emptyVariantRow());
}

function removeVariant(index: number) {
    const removed = form.variants[index];
    form.variants.splice(index, 1);

    form.barcodes.forEach((barcode) => {
        if (barcode.variant_client_key === removed.client_key) {
            barcode.variant_client_key = '';
            barcode.product_variant_id = '';
        }
    });
    form.prices.forEach((price) => {
        if (price.variant_client_key === removed.client_key) {
            price.variant_client_key = '';
            price.product_variant_id = '';
        }
    });
}

function addBarcode() {
    form.barcodes.push(emptyBarcodeRow());
}

function removeBarcode(index: number) {
    form.barcodes.splice(index, 1);
}

function addPrice() {
    const defaultGroup = filteredPriceGroups.value.find((group) => group.is_default);
    form.prices.push(emptyPriceRow(defaultGroup?.id ?? ''));
}

function removePrice(index: number) {
    form.prices.splice(index, 1);
}

function addModifierGroup() {
    form.modifier_groups.push(emptyModifierGroupRow());
}

function removeModifierGroup(index: number) {
    form.modifier_groups.splice(index, 1);
}

function addModifierOption(groupIndex: number) {
    form.modifier_groups[groupIndex].options.push(emptyModifierOptionRow());
}

function removeModifierOption(groupIndex: number, optionIndex: number) {
    form.modifier_groups[groupIndex].options.splice(optionIndex, 1);
}

function addIngredient() {
    form.ingredients.push(emptyIngredientRow());
}

function removeIngredient(index: number) {
    form.ingredients.splice(index, 1);
}

function addComponent() {
    form.components.push(emptyComponentRow());
}

function removeComponent(index: number) {
    form.components.splice(index, 1);
}

function productTypeLabel(type: string): string {
    if (type === 'menu_item') return t('messages.productTypeMenuItemLabel');
    if (type === 'ingredient') return t('messages.productTypeIngredientLabel');
    return t('messages.productTypeRetailLabel');
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.product'),
    );
    if (!confirmed) return;

    const hasNewUploads = form.images.some((row) => !!row.file);

    form.transform((data) => {
        const serialized = serializeProductImages(data.images);
        const payload: Record<string, unknown> = {
            ...data,
            images: serialized.images,
            image_uploads: serialized.image_uploads,
        };

        if (isEdit.value && hasNewUploads) {
            payload._method = 'put';
        }

        return payload;
    });

    if (isEdit.value) {
        if (hasNewUploads) {
            form.post(route('admin.products.update', props.product!.id), { forceFormData: true });
        } else {
            form.put(route('admin.products.update', props.product!.id));
        }
    } else {
        form.post(route('admin.products.store'), hasNewUploads ? { forceFormData: true } : {});
    }
}

function fieldError(key: keyof ProductFormData): string | undefined {
    return form.errors[key];
}

function nestedError(prefix: string): string | undefined {
    return form.errors[prefix as keyof typeof form.errors];
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="products"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.products.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                        <template #default="{ active }">
                            <div v-show="active === 'basic'" class="form-tab-panel">
                                <FormMetaRow
                                    v-if="product"
                                    :items="[
                                        { label: t('fields.recordId'), value: product.id },
                                        { label: t('fields.uuid'), value: product.uuid },
                                    ]"
                                />

                                <FormSection title-key="organization" description-key="organization">
                                    <div class="form-grid">
                                        <FormField label-key="company" required :error="fieldError('company_id')">
                                            <Select v-model="form.company_id">
                                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                                    {{ companyLabel(company) }}
                                                </option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="salesPlan" required hint-key="salesPlan" :error="fieldError('sales_plan_id')">
                                            <Select v-model="form.sales_plan_id">
                                                <option value="">{{ t('fields.selectSalesPlan') }}</option>
                                                <option v-for="plan in filteredSalesPlans" :key="plan.id" :value="plan.id">
                                                    {{ plan.name }} ({{ plan.plan_code }})
                                                </option>
                                            </Select>
                                        </FormField>
                                    </div>
                                </FormSection>

                                <FormSection title-key="productIdentity">
                                    <div class="form-grid">
                                        <FormField label-key="productType" required :error="fieldError('product_type')">
                                            <Select v-model="form.product_type">
                                                <option value="retail">{{ t('messages.productTypeRetail') }}</option>
                                                <option value="menu_item">{{ t('messages.productTypeMenuItem') }}</option>
                                                <option value="ingredient">{{ t('messages.productTypeIngredient') }}</option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="sku" required :error="fieldError('sku')">
                                            <Input v-model="form.sku" :placeholder="placeholder('codeProd')" />
                                        </FormField>
                                        <FormField label-key="name" required :error="fieldError('name')">
                                            <Input v-model="form.name" />
                                        </FormField>
                                    </div>
                                    <p v-if="isMenuItem" class="mt-3 text-sm text-ink-muted">
                                        {{ hint('productMenuItem') }}
                                    </p>
                                    <p v-else-if="isIngredient" class="mt-3 text-sm text-ink-muted">
                                        {{ hint('productIngredient') }}
                                    </p>
                                    <FormField label-key="description" class="mt-4" :error="fieldError('description')">
                                        <FormTextarea v-model="form.description" :placeholder="placeholder('productDescription')" />
                                    </FormField>
                                </FormSection>

                                <FormSection title-key="images" description-key="images">
                                    <ProductImagesField v-model="form.images" />
                                </FormSection>

                                <FormSection title-key="classification">
                                    <div class="form-grid">
                                        <FormField label-key="category" :error="fieldError('category_id')">
                                            <Select v-model="form.category_id">
                                                <option value="">{{ t('fields.none') }}</option>
                                                <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                                    {{ category.name }}
                                                </option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="brand" :error="fieldError('brand_id')">
                                            <Select v-model="form.brand_id">
                                                <option value="">{{ t('fields.none') }}</option>
                                                <option v-for="brand in filteredBrands" :key="brand.id" :value="brand.id">
                                                    {{ brand.name }}
                                                </option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="unit" :error="fieldError('unit_id')">
                                            <Select v-model="form.unit_id">
                                                <option value="">{{ t('fields.none') }}</option>
                                                <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">
                                                    {{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                                                </option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="tax" :error="fieldError('tax_id')">
                                            <Select v-model="form.tax_id">
                                                <option value="">{{ t('fields.none') }}</option>
                                                <option v-for="tax in filteredTaxes" :key="tax.id" :value="tax.id">
                                                    {{ tax.name }} ({{ tax.rate }}%)
                                                </option>
                                            </Select>
                                        </FormField>
                                    </div>
                                </FormSection>

                                <FormSection title-key="inventoryOptions">
                                    <div class="form-toggle-grid">
                                        <FormToggle v-model="form.track_inventory" label-key="trackInventory" />
                                        <FormToggle
                                            v-if="!isIngredient"
                                            v-model="form.has_variants"
                                            label-key="hasVariants"
                                        />
                                        <FormToggle
                                            v-if="isMenuItem"
                                            v-model="form.has_modifiers"
                                            label-key="hasModifiers"
                                        />
                                        <FormToggle
                                            v-if="isMenuItem"
                                            v-model="form.has_components"
                                            label-key="hasComponents"
                                        />
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'inventory'" class="form-tab-panel">
                                <FormSection title-key="stockThresholds" description-key="stockThresholds">
                                    <p class="mb-4 rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink-muted">
                                        {{ hint('stockPerStore') }}
                                    </p>
                                    <div class="form-grid">
                                        <FormField label-key="idealQty" :error="fieldError('ideal_qty')">
                                            <Input
                                                v-model="form.ideal_qty"
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                :placeholder="placeholder('idealQty')"
                                            />
                                        </FormField>
                                        <FormField label-key="warningQty" :error="fieldError('warning_qty')">
                                            <Input
                                                v-model="form.warning_qty"
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                :placeholder="placeholder('warningQty')"
                                            />
                                        </FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'pricing'" class="form-tab-panel">
                                <FormSection title-key="basePricing">
                                    <div class="form-grid">
                                        <FormField label-key="cost" :error="fieldError('cost')">
                                            <Input v-model="form.cost" type="number" step="0.01" />
                                        </FormField>
                                        <FormField label-key="basePrice" :error="fieldError('base_price')">
                                            <Input v-model="form.base_price" type="number" step="0.01" />
                                        </FormField>
                                    </div>
                                </FormSection>

                                <FormSection title-key="priceGroupOverrides" description-key="priceGroupOverrides">
                                    <div v-if="!form.prices.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noPriceOverrides') }}
                                    </div>

                                    <div v-for="(price, index) in form.prices" :key="index" class="mb-4 rounded-lg border border-surface-border p-4">
                                        <div class="form-grid">
                                            <FormField label-key="priceGroup" :error="nestedError(`prices.${index}.price_group_id`)">
                                                <Select v-model="price.price_group_id">
                                                    <option value="">{{ t('fields.selectGroup') }}</option>
                                                    <option v-for="group in filteredPriceGroups" :key="group.id" :value="group.id">
                                                        {{ group.name }} ({{ group.group_code }})
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="variant" :error="nestedError(`prices.${index}.variant_client_key`)">
                                                <Select v-model="price.variant_client_key">
                                                    <option v-for="option in variantOptions" :key="option.key" :value="option.key">
                                                        {{ option.label }}
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="price" :error="nestedError(`prices.${index}.price`)">
                                                <Input v-model="price.price" type="number" step="0.01" />
                                            </FormField>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removePrice(index)">{{ t('forms.actions.removeLine') }}</Button>
                                        </div>
                                    </div>

                                    <Button type="button" variant="secondary" @click="addPrice">{{ t('forms.actions.addPriceRow') }}</Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'modifiers'" class="form-tab-panel">
                                <FormSection title-key="modifierGroups" description-key="modifierGroups">
                                    <div v-if="!form.has_modifiers" class="mb-4 text-sm text-ink-muted">
                                        {{ hint('enableModifiersHint') }}
                                    </div>

                                    <div v-else-if="!form.modifier_groups.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noModifierGroups') }}
                                    </div>

                                    <div
                                        v-for="(group, groupIndex) in form.modifier_groups"
                                        :key="group.client_key"
                                        class="mb-4 rounded-lg border border-surface-border p-4"
                                    >
                                        <div class="form-grid">
                                            <FormField label-key="groupCode" :error="nestedError(`modifier_groups.${groupIndex}.group_code`)">
                                                <Input v-model="group.group_code" :placeholder="placeholder('codeSize')" />
                                            </FormField>
                                            <FormField label-key="name" :error="nestedError(`modifier_groups.${groupIndex}.name`)">
                                                <Input v-model="group.name" :placeholder="placeholder('nameSize')" />
                                            </FormField>
                                            <FormField label-key="selectionType" :error="nestedError(`modifier_groups.${groupIndex}.selection_type`)">
                                                <Select v-model="group.selection_type">
                                                    <option value="single">{{ t('messages.selectionSingle') }}</option>
                                                    <option value="multiple">{{ t('messages.selectionMultiple') }}</option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="sortOrder" :error="nestedError(`modifier_groups.${groupIndex}.sort_order`)">
                                                <Input v-model="group.sort_order" type="number" />
                                            </FormField>
                                            <FormField label-key="minSelections" :error="nestedError(`modifier_groups.${groupIndex}.min_selections`)">
                                                <Input v-model="group.min_selections" type="number" min="0" />
                                            </FormField>
                                            <FormField
                                                v-if="group.selection_type === 'multiple'"
                                                label-key="maxSelections"
                                                :error="nestedError(`modifier_groups.${groupIndex}.max_selections`)"
                                            >
                                                <Input v-model="group.max_selections" type="number" min="1" :placeholder="t('fields.unlimited')" />
                                            </FormField>
                                            <FormField label-key="status" :error="nestedError(`modifier_groups.${groupIndex}.status`)">
                                                <Select v-model="group.status">
                                                    <option value="active">{{ t('common.active') }}</option>
                                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                                </Select>
                                            </FormField>
                                            <div class="flex items-end">
                                                <FormToggle v-model="group.is_required" label-key="requiredGroup" />
                                            </div>
                                        </div>

                                        <div class="mt-4 border-t border-surface-border pt-4">
                                            <p class="mb-3 text-sm font-medium text-ink">{{ t('forms.actions.options') }}</p>
                                            <div
                                                v-for="(option, optionIndex) in group.options"
                                                :key="option.client_key"
                                                class="mb-3 rounded-md bg-surface-muted/40 p-3"
                                            >
                                                <div class="form-grid">
                                                    <FormField
                                                        label-key="optionCode"
                                                        :error="nestedError(`modifier_groups.${groupIndex}.options.${optionIndex}.option_code`)"
                                                    >
                                                        <Input v-model="option.option_code" :placeholder="placeholder('codeLrg')" />
                                                    </FormField>
                                                    <FormField
                                                        label-key="name"
                                                        :error="nestedError(`modifier_groups.${groupIndex}.options.${optionIndex}.name`)"
                                                    >
                                                        <Input v-model="option.name" :placeholder="placeholder('nameLarge')" />
                                                    </FormField>
                                                    <FormField
                                                        label-key="priceAdjustment"
                                                        :error="nestedError(`modifier_groups.${groupIndex}.options.${optionIndex}.price_adjustment`)"
                                                    >
                                                        <Input v-model="option.price_adjustment" type="number" step="0.01" />
                                                    </FormField>
                                                    <FormField
                                                        label-key="sortOrder"
                                                        :error="nestedError(`modifier_groups.${groupIndex}.options.${optionIndex}.sort_order`)"
                                                    >
                                                        <Input v-model="option.sort_order" type="number" />
                                                    </FormField>
                                                    <div class="flex items-end">
                                                        <FormToggle v-model="option.is_default" label-key="defaultOption" />
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-end">
                                                    <Button type="button" variant="ghost" @click="removeModifierOption(groupIndex, optionIndex)">
                                                        {{ t('forms.actions.removeOption') }}
                                                    </Button>
                                                </div>
                                            </div>
                                            <Button type="button" variant="secondary" @click="addModifierOption(groupIndex)">{{ t('forms.actions.addOption') }}</Button>
                                        </div>

                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removeModifierGroup(groupIndex)">{{ t('forms.actions.removeGroup') }}</Button>
                                        </div>
                                    </div>

                                    <Button v-if="form.has_modifiers" type="button" variant="secondary" @click="addModifierGroup">
                                        {{ t('forms.actions.addModifierGroup') }}
                                    </Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'components'" class="form-tab-panel">
                                <FormSection title-key="productComponents" description-key="productComponents">
                                    <div v-if="!form.has_components" class="mb-4 text-sm text-ink-muted">
                                        {{ hint('enableComponentsHint') }}
                                    </div>

                                    <div v-else-if="!filteredComponentProducts.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noComponentProducts') }}
                                    </div>

                                    <div v-else-if="!form.components.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noComponentsLinked') }}
                                    </div>

                                    <div
                                        v-for="(component, index) in form.components"
                                        :key="index"
                                        class="mb-4 rounded-lg border border-surface-border p-4"
                                    >
                                        <div class="form-grid">
                                            <FormField
                                                label-key="componentProduct"
                                                :error="nestedError(`components.${index}.component_product_id`)"
                                            >
                                                <Select v-model="component.component_product_id">
                                                    <option value="">{{ t('fields.selectProduct') }}</option>
                                                    <option
                                                        v-for="item in filteredComponentProducts"
                                                        :key="item.id"
                                                        :value="item.id"
                                                    >
                                                        {{ item.name }} ({{ item.sku }}) — {{ productTypeLabel(item.product_type) }}
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="quantity" :error="nestedError(`components.${index}.quantity`)">
                                                <Input v-model="component.quantity" type="number" step="0.0001" min="0.0001" />
                                            </FormField>
                                            <FormField label-key="unit" :error="nestedError(`components.${index}.unit_id`)">
                                                <Select v-model="component.unit_id">
                                                    <option value="">{{ t('fields.defaultOption') }}</option>
                                                    <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">
                                                        {{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="sortOrder" :error="nestedError(`components.${index}.sort_order`)">
                                                <Input v-model="component.sort_order" type="number" />
                                            </FormField>
                                            <FormField label-key="notes" :error="nestedError(`components.${index}.notes`)">
                                                <Input v-model="component.notes" :placeholder="placeholder('bundleNotes')" />
                                            </FormField>
                                            <div class="flex items-end">
                                                <FormToggle v-model="component.is_optional" label-key="optionalComponent" />
                                            </div>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removeComponent(index)">{{ t('forms.actions.removeLine') }}</Button>
                                        </div>
                                    </div>

                                    <Button v-if="form.has_components" type="button" variant="secondary" @click="addComponent">
                                        {{ t('forms.actions.addComponent') }}
                                    </Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'ingredients'" class="form-tab-panel">
                                <FormSection title-key="recipeIngredients" description-key="recipeIngredients">
                                    <div v-if="!filteredIngredientProducts.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noIngredientProducts') }}
                                    </div>

                                    <div v-if="!form.ingredients.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noIngredientsLinked') }}
                                    </div>

                                    <div
                                        v-for="(ingredient, index) in form.ingredients"
                                        :key="index"
                                        class="mb-4 rounded-lg border border-surface-border p-4"
                                    >
                                        <div class="form-grid">
                                            <FormField
                                                label-key="product"
                                                :error="nestedError(`ingredients.${index}.ingredient_product_id`)"
                                            >
                                                <Select v-model="ingredient.ingredient_product_id">
                                                    <option value="">{{ t('fields.selectIngredient') }}</option>
                                                    <option
                                                        v-for="item in filteredIngredientProducts"
                                                        :key="item.id"
                                                        :value="item.id"
                                                    >
                                                        {{ item.name }} ({{ item.sku }})
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="quantity" :error="nestedError(`ingredients.${index}.quantity`)">
                                                <Input v-model="ingredient.quantity" type="number" step="0.0001" min="0.0001" />
                                            </FormField>
                                            <FormField label-key="unit" :error="nestedError(`ingredients.${index}.unit_id`)">
                                                <Select v-model="ingredient.unit_id">
                                                    <option value="">{{ t('fields.defaultOption') }}</option>
                                                    <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">
                                                        {{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <FormField label-key="sortOrder" :error="nestedError(`ingredients.${index}.sort_order`)">
                                                <Input v-model="ingredient.sort_order" type="number" />
                                            </FormField>
                                            <FormField label-key="notes" :error="nestedError(`ingredients.${index}.notes`)">
                                                <Input v-model="ingredient.notes" :placeholder="placeholder('prepNotes')" />
                                            </FormField>
                                            <div class="flex items-end">
                                                <FormToggle v-model="ingredient.is_optional" label-key="optionalIngredient" />
                                            </div>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removeIngredient(index)">{{ t('forms.actions.removeLine') }}</Button>
                                        </div>
                                    </div>

                                    <Button type="button" variant="secondary" @click="addIngredient">{{ t('forms.actions.addIngredient') }}</Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'variants'" class="form-tab-panel">
                                <FormSection title-key="productVariants" description-key="productVariants">
                                    <div v-if="!form.variants.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noVariants') }}
                                    </div>

                                    <div v-for="(variant, index) in form.variants" :key="variant.client_key" class="mb-4 rounded-lg border border-surface-border p-4">
                                        <div class="form-grid">
                                            <FormField label-key="variantCode" :error="nestedError(`variants.${index}.variant_code`)">
                                                <Input v-model="variant.variant_code" />
                                            </FormField>
                                            <FormField label-key="name" :error="nestedError(`variants.${index}.name`)">
                                                <Input v-model="variant.name" />
                                            </FormField>
                                            <FormField label-key="sku" :error="nestedError(`variants.${index}.sku`)">
                                                <Input v-model="variant.sku" />
                                            </FormField>
                                            <FormField label-key="sortOrder" :error="nestedError(`variants.${index}.sort_order`)">
                                                <Input v-model="variant.sort_order" type="number" />
                                            </FormField>
                                            <FormField label-key="cost" :error="nestedError(`variants.${index}.cost`)">
                                                <Input v-model="variant.cost" type="number" step="0.01" />
                                            </FormField>
                                            <FormField label-key="sellingPrice" :error="nestedError(`variants.${index}.selling_price`)">
                                                <Input v-model="variant.selling_price" type="number" step="0.01" />
                                            </FormField>
                                            <FormField label-key="status" :error="nestedError(`variants.${index}.status`)">
                                                <Select v-model="variant.status">
                                                    <option value="active">{{ t('common.active') }}</option>
                                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                                </Select>
                                            </FormField>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removeVariant(index)">{{ t('forms.actions.removeLine') }}</Button>
                                        </div>
                                    </div>

                                    <Button type="button" variant="secondary" @click="addVariant">{{ t('forms.actions.addVariant') }}</Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'barcodes'" class="form-tab-panel">
                                <FormSection title-key="barcodes" description-key="barcodes">
                                    <div v-if="!form.barcodes.length" class="mb-4 text-sm text-ink-muted">
                                        {{ t('messages.noBarcodes') }}
                                    </div>

                                    <div v-for="(barcode, index) in form.barcodes" :key="index" class="mb-4 rounded-lg border border-surface-border p-4">
                                        <div class="form-grid">
                                            <FormField label-key="barcode" :error="nestedError(`barcodes.${index}.barcode`)">
                                                <Input v-model="barcode.barcode" />
                                            </FormField>
                                            <FormField label-key="variant" :error="nestedError(`barcodes.${index}.variant_client_key`)">
                                                <Select v-model="barcode.variant_client_key">
                                                    <option v-for="option in variantOptions" :key="option.key" :value="option.key">
                                                        {{ option.label }}
                                                    </option>
                                                </Select>
                                            </FormField>
                                            <div class="flex items-end">
                                                <FormToggle v-model="barcode.is_primary" label-key="primaryBarcode" />
                                            </div>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <Button type="button" variant="ghost" @click="removeBarcode(index)">{{ t('forms.actions.removeLine') }}</Button>
                                        </div>
                                    </div>

                                    <Button type="button" variant="secondary" @click="addBarcode">{{ t('forms.actions.addBarcode') }}</Button>
                                </FormSection>
                            </div>

                            <div v-show="active === 'status'" class="form-tab-panel">
                                <FormSection title-key="lifecycle">
                                    <FormField label-key="status" required :error="fieldError('status')">
                                        <Select v-model="form.status">
                                            <option value="active">{{ t('common.active') }}</option>
                                            <option value="inactive">{{ t('common.inactive') }}</option>
                                            <option value="discontinued">{{ t('filters.discontinued') }}</option>
                                        </Select>
                                    </FormField>
                                </FormSection>

                                <FormSection v-if="product" title-key="auditTrail">
                                    <div class="form-audit-grid">
                                        <div><p class="form-audit-label">{{ t('fields.type') }}</p><p class="form-audit-value">{{ productTypeLabel(product.product_type ?? 'retail') }}</p></div>
                                        <div><p class="form-audit-label">{{ t('fields.created') }}</p><p class="form-audit-value">{{ product.created_at }}</p></div>
                                        <div><p class="form-audit-label">{{ t('fields.lastUpdated') }}</p><p class="form-audit-value">{{ product.updated_at }}</p></div>
                                    </div>
                                </FormSection>
                            </div>
                        </template>
                    </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.products.index')"
                    :submit-label="isEdit ? submit('saveProduct') : submit('createProduct')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
