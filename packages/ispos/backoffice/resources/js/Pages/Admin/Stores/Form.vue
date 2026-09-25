<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormMetaRow from '@/Components/forms/FormMetaRow.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import InputError from '@/Components/InputError.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import {
    defaultStoreForm,
    storeOperatingDays,
    type StoreFormData,
    type StoreRecord,
} from '@/types/store';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

const page = useModulePage('stores');
const { t, placeholder, submit, tab, tabDesc } = useLocale();

const props = defineProps<{
    store: StoreRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    managers: Array<{ id: string; name: string; email: string; company_id: string | null }>;
    salesPlans: Array<{ id: string; name: string; plan_code: string; company_id: string; status: string }>;
}>();

const activeTab = ref('basic');
const isEdit = computed(() => !!props.store);
const pageTitle = useFormPageTitle('store', isEdit);

const tabs = computed(() => [
    { key: 'basic', label: tab('basic'), description: tabDesc('basicStore') },
    { key: 'tax', label: tab('taxLegal'), description: tabDesc('taxLegal') },
    { key: 'contact', label: tab('contact'), description: tabDesc('contactStore') },
    { key: 'address', label: tab('location'), description: tabDesc('location') },
    { key: 'operations', label: tab('operations'), description: tabDesc('operations') },
    { key: 'modules', label: tab('modules'), description: tabDesc('salesChannels') },
    { key: 'branding', label: tab('branding'), description: tabDesc('brandingStore') },
    { key: 'status', label: tab('status'), description: tabDesc('status') },
]);

const form = useForm<StoreFormData>(
    defaultStoreForm(props.store, props.store?.company_id ?? props.companies[0]?.id ?? ''),
);

const activeTabMeta = computed(() => tabs.value.find((item) => item.key === activeTab.value) ?? tabs.value[0]);

const tabErrorConfig: FormTabErrorConfig[] = [
    { key: 'basic', requiredFields: ['company_id', 'store_code', 'store_name'] },
    { key: 'tax', errorPrefixes: ['tax_id', 'business_permit_no', 'bir_reference'] },
    { key: 'contact', errorPrefixes: ['email', 'phone', 'mobile'] },
    { key: 'address', errorPrefixes: ['address_line_1', 'city', 'state', 'postal_code', 'country', 'latitude', 'longitude'] },
    { key: 'operations', requiredFields: ['currency', 'timezone'], errorPrefixes: ['operating_days', 'opening_time', 'closing_time'] },
    { key: 'modules', errorPrefixes: ['enabled_modules', 'pos_enabled', 'online_ordering_enabled'] },
    { key: 'branding', errorPrefixes: ['logo', 'remove_logo', 'receipt_header', 'receipt_footer'] },
    { key: 'status', requiredFields: ['status'] },
];

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

const filteredManagers = computed(() =>
    props.managers.filter((manager) => !form.company_id || manager.company_id === form.company_id),
);

const filteredSalesPlans = computed(() =>
    props.salesPlans.filter((plan) => !form.company_id || plan.company_id === form.company_id),
);

watch(
    () => form.company_id,
    () => {
        if (form.sales_plan_id && !filteredSalesPlans.value.some((plan) => plan.id === form.sales_plan_id)) {
            form.sales_plan_id = '';
        }
    },
);

const selectedLogoPreview = ref<string | null>(null);

const logoPreview = computed(() => {
    if (selectedLogoPreview.value) {
        return selectedLogoPreview.value;
    }

    return form.remove_logo ? null : props.store?.logo_url ?? null;
});

function releaseLogoPreview() {
    if (selectedLogoPreview.value) {
        URL.revokeObjectURL(selectedLogoPreview.value);
        selectedLogoPreview.value = null;
    }
}

function onLogoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    releaseLogoPreview();
    form.logo = file;

    if (file) {
        selectedLogoPreview.value = URL.createObjectURL(file);
        form.remove_logo = false;
    }
}

onUnmounted(releaseLogoPreview);

function companyLabel(company: (typeof props.companies)[number]): string {
    return company.display_name || company.name;
}

function toggleOperatingDay(day: string) {
    form.operating_days = form.operating_days.includes(day)
        ? form.operating_days.filter((value) => value !== day)
        : [...form.operating_days, day];
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.store'),
    );
    if (!confirmed) return;

    const hasUpload = form.logo instanceof File;

    form.transform((data) => {
        const payload: Record<string, unknown> = { ...data };

        if (!(payload.logo instanceof File)) {
            delete payload.logo;
        }

        // PHP only parses multipart bodies on POST, so file uploads spoof the PUT method.
        if (isEdit.value && hasUpload) {
            payload._method = 'put';
        }

        return payload;
    });

    if (isEdit.value) {
        if (hasUpload) {
            form.post(route('admin.stores.update', props.store!.id), { forceFormData: true });
        } else {
            form.put(route('admin.stores.update', props.store!.id));
        }
    } else {
        form.post(route('admin.stores.store'), hasUpload ? { forceFormData: true } : {});
    }
}

function fieldError(key: keyof StoreFormData): string | undefined {
    return form.errors[key];
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="stores"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.stores.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                        <template #default="{ active }">
                            <div v-show="active === 'basic'" class="form-tab-panel">
                                <FormMetaRow
                                    v-if="store"
                                    :items="[
                                        { label: t('fields.recordId'), value: store.id },
                                        { label: t('fields.uuid'), value: store.uuid },
                                    ]"
                                />

                                <FormSection title-key="companyAndCodes" description-key="companyAndCodes">
                                    <FormField label-key="company" required :error="fieldError('company_id')">
                                        <Select v-model="form.company_id">
                                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                                {{ companyLabel(company) }}
                                            </option>
                                        </Select>
                                    </FormField>
                                    <div class="form-grid mt-4">
                                        <FormField label-key="storeCode" required :error="fieldError('store_code')"><Input v-model="form.store_code" :placeholder="placeholder('codeMain')" /></FormField>
                                        <FormField label-key="branchCode" :error="fieldError('branch_code')"><Input v-model="form.branch_code" /></FormField>
                                    </div>
                                </FormSection>

                                <FormSection title-key="storeProfile">
                                    <div class="form-grid">
                                        <FormField label-key="storeName" required :error="fieldError('store_name')"><Input v-model="form.store_name" /></FormField>
                                        <FormField label-key="legalName" :error="fieldError('legal_name')"><Input v-model="form.legal_name" /></FormField>
                                        <FormField label-key="storeType" :error="fieldError('store_type')">
                                            <Select v-model="form.store_type">
                                                <option value="">{{ t('fields.selectType') }}</option>
                                                <option value="head_office">{{ t('messages.storeTypeHeadOffice') }}</option>
                                                <option value="branch">{{ t('messages.storeTypeBranch') }}</option>
                                                <option value="warehouse">{{ t('messages.storeTypeWarehouse') }}</option>
                                                <option value="outlet">{{ t('messages.storeTypeOutlet') }}</option>
                                                <option value="kiosk">{{ t('messages.storeTypeKiosk') }}</option>
                                                <option value="popup">{{ t('messages.storeTypePopup') }}</option>
                                                <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="storeCategory" :error="fieldError('store_category')">
                                            <Select v-model="form.store_category">
                                                <option value="">{{ t('fields.selectType') }}</option>
                                                <option value="retail">{{ t('messages.storeCategoryRetail') }}</option>
                                                <option value="wholesale">{{ t('messages.storeCategoryWholesale') }}</option>
                                                <option value="restaurant">{{ t('messages.storeCategoryRestaurant') }}</option>
                                                <option value="cafe">{{ t('messages.storeCategoryCafe') }}</option>
                                                <option value="grocery">{{ t('messages.storeCategoryGrocery') }}</option>
                                                <option value="pharmacy">{{ t('messages.storeCategoryPharmacy') }}</option>
                                                <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                            </Select>
                                        </FormField>
                                    </div>
                                    <FormField label-key="description" class="mt-4" :error="fieldError('description')">
                                        <FormTextarea v-model="form.description" :placeholder="placeholder('locationNotes')" />
                                    </FormField>
                                    <FormField label-key="salesPlan" class="mt-4" hint-key="salesPlan" :error="fieldError('sales_plan_id')">
                                        <Select v-model="form.sales_plan_id">
                                            <option value="">{{ t('fields.selectSalesPlan') }}</option>
                                            <option v-for="plan in filteredSalesPlans" :key="plan.id" :value="plan.id">
                                                {{ plan.name }} ({{ plan.plan_code }})
                                            </option>
                                        </Select>
                                    </FormField>
                                    <FormField label-key="storeManager" class="mt-4" hint-key="storeManager" :error="fieldError('manager_id')">
                                        <Select v-model="form.manager_id">
                                            <option value="">{{ t('fields.noManagerAssigned') }}</option>
                                            <option v-for="manager in filteredManagers" :key="manager.id" :value="manager.id">
                                                {{ manager.name }} · {{ manager.email }}
                                            </option>
                                        </Select>
                                    </FormField>
                                </FormSection>
                            </div>

                            <div v-show="active === 'tax'" class="form-tab-panel">
                                <FormSection title-key="taxCompliance">
                                    <div class="form-grid">
                                        <FormField label-key="tin" :error="fieldError('tin')"><Input v-model="form.tin" /></FormField>
                                        <FormField label-key="defaultTaxRate" :error="fieldError('default_tax_rate')"><Input v-model="form.default_tax_rate" type="number" step="0.01" /></FormField>
                                        <FormField label-key="birRegistrationNo" :error="fieldError('bir_registration_no')"><Input v-model="form.bir_registration_no" /></FormField>
                                        <FormField label-key="businessPermitNo" :error="fieldError('business_permit_no')"><Input v-model="form.business_permit_no" /></FormField>
                                    </div>
                                </FormSection>
                                <FormSection title-key="internalReferences" description-key="internalReferences">
                                    <div class="form-grid">
                                        <FormField label-key="warehouseId" :error="fieldError('warehouse_id')"><Input v-model="form.warehouse_id" /></FormField>
                                        <FormField label-key="priceGroupId" :error="fieldError('price_group_id')"><Input v-model="form.price_group_id" /></FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'contact'" class="form-tab-panel">
                                <FormSection title-key="contactInformation">
                                    <div class="form-grid">
                                        <FormField label-key="email" :error="fieldError('email')"><Input v-model="form.email" type="email" /></FormField>
                                        <FormField label-key="phone" :error="fieldError('phone')"><Input v-model="form.phone" /></FormField>
                                        <FormField label-key="mobile" :error="fieldError('mobile')"><Input v-model="form.mobile" /></FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'address'" class="form-tab-panel">
                                <FormSection title-key="streetAddress">
                                    <FormField label-key="addressLine1" :error="fieldError('address_line_1')"><Input v-model="form.address_line_1" /></FormField>
                                    <FormField label-key="addressLine2" class="mt-4" :error="fieldError('address_line_2')"><Input v-model="form.address_line_2" /></FormField>
                                    <div class="form-grid mt-4">
                                        <FormField label-key="barangay" :error="fieldError('barangay')"><Input v-model="form.barangay" /></FormField>
                                        <FormField label-key="city" :error="fieldError('city')"><Input v-model="form.city" /></FormField>
                                        <FormField label-key="province" :error="fieldError('province')"><Input v-model="form.province" /></FormField>
                                        <FormField label-key="region" :error="fieldError('region')"><Input v-model="form.region" /></FormField>
                                        <FormField label-key="country" :error="fieldError('country')"><Input v-model="form.country" maxlength="2" /></FormField>
                                        <FormField label-key="postalCode" :error="fieldError('postal_code')"><Input v-model="form.postal_code" /></FormField>
                                    </div>
                                </FormSection>
                                <FormSection title-key="coordinates" description-key="coordinates">
                                    <div class="form-grid">
                                        <FormField label-key="latitude" :error="fieldError('latitude')"><Input v-model="form.latitude" type="number" step="any" /></FormField>
                                        <FormField label-key="longitude" :error="fieldError('longitude')"><Input v-model="form.longitude" type="number" step="any" /></FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'operations'" class="form-tab-panel">
                                <FormSection title-key="regionalSettings">
                                    <div class="form-grid">
                                        <FormField label-key="currency" required :error="fieldError('currency')"><Input v-model="form.currency" maxlength="3" /></FormField>
                                        <FormField label-key="timezone" required :error="fieldError('timezone')"><Input v-model="form.timezone" /></FormField>
                                    </div>
                                </FormSection>
                                <FormSection title-key="operatingHours">
                                    <FormToggle v-model="form.is_24_hours" label-key="open24Hours" class="mb-4" />
                                    <div class="form-grid">
                                        <FormField label-key="openingTime" :error="fieldError('opening_time')"><Input v-model="form.opening_time" type="time" :disabled="form.is_24_hours" /></FormField>
                                        <FormField label-key="closingTime" :error="fieldError('closing_time')"><Input v-model="form.closing_time" type="time" :disabled="form.is_24_hours" /></FormField>
                                    </div>
                                    <div class="mt-4">
                                        <p class="form-label mb-2">{{ t('fields.operatingDays') }}</p>
                                        <div class="form-day-grid">
                                            <label
                                                v-for="day in storeOperatingDays"
                                                :key="day"
                                                class="form-day-chip"
                                                :class="{ 'form-day-chip--active': form.operating_days.includes(day) }"
                                            >
                                                <input type="checkbox" class="sr-only" :checked="form.operating_days.includes(day)" @change="toggleOperatingDay(day)" />
                                                {{ day }}
                                            </label>
                                        </div>
                                        <p v-if="fieldError('operating_days')" class="form-error mt-1.5">{{ fieldError('operating_days') }}</p>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'modules'" class="form-tab-panel">
                                <FormSection title-key="salesChannels" description-key="salesChannels">
                                    <div class="form-toggle-grid">
                                        <FormToggle v-model="form.enable_pos" label-key="enablePos" />
                                        <FormToggle v-model="form.enable_inventory" label-key="enableInventory" />
                                        <FormToggle v-model="form.enable_online_ordering" label-key="enableOnlineOrdering" />
                                        <FormToggle v-model="form.enable_delivery" label-key="enableDelivery" />
                                        <FormToggle v-model="form.enable_pickup" label-key="enablePickup" />
                                        <FormToggle v-model="form.enable_dine_in" label-key="enableDineIn" />
                                        <FormToggle v-model="form.enable_takeaway" label-key="enableTakeaway" />
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'branding'" class="form-tab-panel">
                                <FormSection title-key="storeBranding">
                                    <div class="mb-5 flex flex-wrap items-start gap-4">
                                        <div
                                            class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-surface-muted"
                                        >
                                            <img
                                                v-if="logoPreview"
                                                :src="logoPreview"
                                                alt=""
                                                class="h-full w-full object-contain p-2"
                                            />
                                            <span v-else class="text-xs font-medium text-ink-muted">Logo</span>
                                        </div>
                                        <div class="min-w-[16rem] flex-1">
                                            <span class="ui-label">{{ t('fields.logo') }}</span>
                                            <label
                                                class="mt-1.5 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-line bg-surface-muted/40 px-4 py-4 transition hover:border-accent/40 hover:bg-accent-soft/20"
                                            >
                                                <span class="text-sm font-medium text-ink">{{ t('hints.logoUpload') }}</span>
                                                <span class="mt-1 text-xs text-ink-muted">{{ t('hints.logoHelp') }}</span>
                                                <input
                                                    type="file"
                                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                    class="sr-only"
                                                    @change="onLogoChange"
                                                />
                                            </label>
                                            <InputError :message="fieldError('logo')" />
                                            <label
                                                v-if="store?.logo_url"
                                                class="mt-2 flex items-center gap-2 text-sm text-ink"
                                            >
                                                <input v-model="form.remove_logo" type="checkbox" class="rounded border-line" />
                                                {{ t('hints.removeLogo') }}
                                            </label>
                                        </div>
                                    </div>
                                    <FormField label-key="receiptHeader" class="mt-4" :error="fieldError('receipt_header')"><FormTextarea v-model="form.receipt_header" /></FormField>
                                    <FormField label-key="receiptFooter" class="mt-4" :error="fieldError('receipt_footer')"><FormTextarea v-model="form.receipt_footer" /></FormField>
                                </FormSection>
                            </div>

                            <div v-show="active === 'status'" class="form-tab-panel">
                                <FormSection title-key="lifecycle">
                                    <div class="form-grid">
                                        <FormField label-key="status" required :error="fieldError('status')">
                                            <Select v-model="form.status">
                                                <option value="active">{{ t('common.active') }}</option>
                                                <option value="inactive">{{ t('common.inactive') }}</option>
                                                <option value="suspended">{{ t('filters.suspended') }}</option>
                                                <option value="closed">{{ t('filters.closed') }}</option>
                                            </Select>
                                        </FormField>
                                        <div class="flex items-end"><FormToggle v-model="form.is_active" label-key="activeFlag" /></div>
                                        <FormField label-key="openedAt" :error="fieldError('opened_at')"><Input v-model="form.opened_at" type="date" /></FormField>
                                        <FormField label-key="closedAt" :error="fieldError('closed_at')"><Input v-model="form.closed_at" type="date" /></FormField>
                                    </div>
                                </FormSection>
                                <FormSection v-if="store" title-key="auditTrail">
                                    <div class="form-audit-grid">
                                        <div><p class="form-audit-label">{{ t('fields.created') }}</p><p class="form-audit-value">{{ store.created_at }}</p></div>
                                        <div><p class="form-audit-label">{{ t('fields.lastUpdated') }}</p><p class="form-audit-value">{{ store.updated_at }}</p></div>
                                    </div>
                                </FormSection>
                            </div>
                        </template>
                    </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.stores.index')"
                    :submit-label="isEdit ? submit('saveStore') : submit('createStore')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
