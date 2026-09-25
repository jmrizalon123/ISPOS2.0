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
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import { defaultCompanyForm, type CompanyFormData, type CompanyRecord } from '@/types/company';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = useModulePage('companies');
const { t, placeholder, submit, tab, tabDesc } = useLocale();

const props = defineProps<{
    company: CompanyRecord | null;
}>();

const activeTab = ref('basic');
const isEdit = computed(() => !!props.company);
const pageTitle = useFormPageTitle('company', isEdit);

const tabs = computed(() => [
    { key: 'basic', label: tab('basic'), description: tabDesc('basicCompany') },
    { key: 'tax', label: tab('tax'), description: tabDesc('tax') },
    { key: 'contact', label: tab('contact'), description: tabDesc('contact') },
    { key: 'address', label: tab('address'), description: tabDesc('address') },
    { key: 'branding', label: tab('branding'), description: tabDesc('branding') },
    { key: 'finance', label: tab('finance'), description: tabDesc('finance') },
    { key: 'modules', label: tab('modules'), description: tabDesc('modules') },
    { key: 'subscription', label: tab('subscription'), description: tabDesc('subscription') },
    { key: 'status', label: tab('status'), description: tabDesc('status') },
]);

const form = useForm<CompanyFormData>(defaultCompanyForm(props.company));

const activeTabMeta = computed(() => tabs.value.find((item) => item.key === activeTab.value) ?? tabs.value[0]);

const tabErrorConfig: FormTabErrorConfig[] = [
    { key: 'basic', requiredFields: ['company_code', 'name'] },
    { key: 'tax', errorPrefixes: ['tax_id', 'business_registration_no', 'vat_registration_no'] },
    { key: 'contact', errorPrefixes: ['email', 'phone', 'website'] },
    { key: 'address', errorPrefixes: ['address_line_1', 'city', 'state', 'postal_code', 'country'] },
    { key: 'branding', errorPrefixes: ['logo_url', 'receipt_header', 'receipt_footer'] },
    { key: 'finance', requiredFields: ['base_currency', 'timezone'], errorPrefixes: ['accounting_method', 'fiscal_year_start'] },
    { key: 'modules', errorPrefixes: ['enabled_modules'] },
    { key: 'subscription', errorPrefixes: ['subscription_plan', 'subscription_status', 'subscription_expires_at'] },
    { key: 'status', requiredFields: ['status'] },
];

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.company'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.companies.update', props.company!.id));
    } else {
        form.post(route('admin.companies.store'));
    }
}

function fieldError(key: keyof CompanyFormData): string | undefined {
    return form.errors[key];
}

function swatchColor(value: string): string {
    return /^#[0-9A-Fa-f]{6}$/.test(value) ? value : 'rgb(var(--color-surface-muted))';
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="companies"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.companies.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                        <template #default="{ active }">
                            <div v-show="active === 'basic'" class="form-tab-panel">
                                <FormMetaRow
                                    v-if="company"
                                    :items="[
                                        { label: t('fields.recordId'), value: company.id },
                                        { label: t('fields.uuid'), value: company.uuid },
                                    ]"
                                />

                                <FormSection title-key="organizationIdentity" description-key="organizationIdentity">
                                    <div class="form-grid">
                                        <FormField label-key="companyCode" required :error="fieldError('company_code')">
                                            <Input v-model="form.company_code" :placeholder="placeholder('codeDemo')" />
                                        </FormField>
                                        <FormField label-key="companyType" :error="fieldError('company_type')">
                                            <Select v-model="form.company_type">
                                                <option value="">{{ t('fields.selectType') }}</option>
                                                <option value="sole_proprietorship">{{ t('messages.companyTypeSole') }}</option>
                                                <option value="partnership">{{ t('messages.companyTypePartnership') }}</option>
                                                <option value="corporation">{{ t('messages.companyTypeCorporation') }}</option>
                                                <option value="cooperative">{{ t('messages.companyTypeCooperative') }}</option>
                                                <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                            </Select>
                                        </FormField>
                                    </div>
                                </FormSection>

                                <FormSection title-key="naming" description-key="naming">
                                    <div class="form-grid">
                                        <FormField label-key="registeredName" required :error="fieldError('name')">
                                            <Input v-model="form.name" />
                                        </FormField>
                                        <FormField label-key="legalName" :error="fieldError('legal_name')">
                                            <Input v-model="form.legal_name" />
                                        </FormField>
                                        <FormField label-key="displayName" hint-key="displayName" :error="fieldError('display_name')">
                                            <Input v-model="form.display_name" />
                                        </FormField>
                                        <FormField label-key="tradeName" :error="fieldError('trade_name')">
                                            <Input v-model="form.trade_name" />
                                        </FormField>
                                        <FormField label-key="industry" :error="fieldError('industry')">
                                            <Input v-model="form.industry" :placeholder="placeholder('industry')" />
                                        </FormField>
                                    </div>
                                    <FormField label-key="description" class="mt-4" :error="fieldError('description')">
                                        <FormTextarea v-model="form.description" :rows="4" :placeholder="placeholder('businessOverview')" />
                                    </FormField>
                                </FormSection>
                            </div>

                            <div v-show="active === 'tax'" class="form-tab-panel">
                                <FormSection title-key="taxProfile" description-key="taxProfile">
                                    <div class="form-grid">
                                        <FormField label-key="tin" :error="fieldError('tin')">
                                            <Input v-model="form.tin" />
                                        </FormField>
                                        <FormField label-key="taxpayerType" :error="fieldError('taxpayer_type')">
                                            <Select v-model="form.taxpayer_type">
                                                <option value="">{{ t('fields.selectType') }}</option>
                                                <option value="vat">{{ t('messages.taxpayerVat') }}</option>
                                                <option value="non_vat">{{ t('messages.taxpayerNonVat') }}</option>
                                                <option value="percentage_tax">{{ t('messages.taxpayerPercentage') }}</option>
                                                <option value="exempt">{{ t('messages.taxpayerExempt') }}</option>
                                            </Select>
                                        </FormField>
                                        <FormField label-key="defaultTaxRate" :error="fieldError('default_tax_rate')">
                                            <Input v-model="form.default_tax_rate" type="number" step="0.01" />
                                        </FormField>
                                        <div class="flex items-end">
                                            <FormToggle v-model="form.vat_registered" label-key="vatRegistered" />
                                        </div>
                                    </div>
                                </FormSection>

                                <FormSection title-key="governmentRegistrations">
                                    <div class="form-grid">
                                        <FormField label-key="birRegistrationNo" :error="fieldError('bir_registration_no')">
                                            <Input v-model="form.bir_registration_no" />
                                        </FormField>
                                        <FormField label-key="secRegistrationNo" :error="fieldError('sec_registration_no')">
                                            <Input v-model="form.sec_registration_no" />
                                        </FormField>
                                        <FormField label-key="dtiRegistrationNo" :error="fieldError('dti_registration_no')">
                                            <Input v-model="form.dti_registration_no" />
                                        </FormField>
                                        <FormField label-key="businessPermitNo" :error="fieldError('business_permit_no')">
                                            <Input v-model="form.business_permit_no" />
                                        </FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'contact'" class="form-tab-panel">
                                <FormSection title-key="contactChannels">
                                    <div class="form-grid">
                                        <FormField label-key="email" :error="fieldError('email')">
                                            <Input v-model="form.email" type="email" />
                                        </FormField>
                                        <FormField label-key="website" :error="fieldError('website')">
                                            <Input v-model="form.website" type="url" :placeholder="placeholder('website')" />
                                        </FormField>
                                        <FormField label-key="phone" :error="fieldError('phone')">
                                            <Input v-model="form.phone" />
                                        </FormField>
                                        <FormField label-key="mobile" :error="fieldError('mobile')">
                                            <Input v-model="form.mobile" />
                                        </FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'address'" class="form-tab-panel">
                                <FormSection title-key="businessAddress">
                                    <FormField label-key="addressLine1" :error="fieldError('address_line_1')">
                                        <Input v-model="form.address_line_1" />
                                    </FormField>
                                    <FormField label-key="addressLine2" class="mt-4" :error="fieldError('address_line_2')">
                                        <Input v-model="form.address_line_2" />
                                    </FormField>
                                    <div class="form-grid mt-4">
                                        <FormField label-key="barangay" :error="fieldError('barangay')"><Input v-model="form.barangay" /></FormField>
                                        <FormField label-key="city" :error="fieldError('city')"><Input v-model="form.city" /></FormField>
                                        <FormField label-key="province" :error="fieldError('province')"><Input v-model="form.province" /></FormField>
                                        <FormField label-key="region" :error="fieldError('region')"><Input v-model="form.region" /></FormField>
                                        <FormField label-key="country" hint-key="countryIso" :error="fieldError('country')"><Input v-model="form.country" maxlength="2" /></FormField>
                                        <FormField label-key="postalCode" :error="fieldError('postal_code')"><Input v-model="form.postal_code" /></FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'branding'" class="form-tab-panel">
                                <FormSection title-key="visualIdentity">
                                    <div class="form-grid">
                                        <FormField label-key="logoUrl" :error="fieldError('logo')"><Input v-model="form.logo" /></FormField>
                                        <FormField label-key="faviconUrl" :error="fieldError('favicon')"><Input v-model="form.favicon" /></FormField>
                                        <FormField label-key="primaryColor" :error="fieldError('primary_color')">
                                            <div class="form-color-field">
                                                <span class="form-color-swatch" :style="{ backgroundColor: swatchColor(form.primary_color) }" />
                                                <div class="min-w-0 flex-1"><Input v-model="form.primary_color" :placeholder="placeholder('primaryColor')" /></div>
                                            </div>
                                        </FormField>
                                        <FormField label-key="secondaryColor" :error="fieldError('secondary_color')">
                                            <div class="form-color-field">
                                                <span class="form-color-swatch" :style="{ backgroundColor: swatchColor(form.secondary_color) }" />
                                                <div class="min-w-0 flex-1"><Input v-model="form.secondary_color" :placeholder="placeholder('secondaryColor')" /></div>
                                            </div>
                                        </FormField>
                                    </div>
                                </FormSection>
                                <FormSection title-key="receiptCopy">
                                    <FormField label-key="receiptHeader" :error="fieldError('receipt_header')"><FormTextarea v-model="form.receipt_header" /></FormField>
                                    <FormField label-key="receiptFooter" class="mt-4" :error="fieldError('receipt_footer')"><FormTextarea v-model="form.receipt_footer" /></FormField>
                                </FormSection>
                            </div>

                            <div v-show="active === 'finance'" class="form-tab-panel">
                                <FormSection title-key="currencyAccounting">
                                    <div class="form-grid">
                                        <FormField label-key="baseCurrency" required :error="fieldError('base_currency')"><Input v-model="form.base_currency" maxlength="3" /></FormField>
                                        <FormField label-key="currencySymbol" :error="fieldError('currency_symbol')"><Input v-model="form.currency_symbol" /></FormField>
                                        <FormField label-key="accountingMethod" :error="fieldError('accounting_method')">
                                            <Select v-model="form.accounting_method"><option value="accrual">{{ t('messages.accountingAccrual') }}</option><option value="cash">{{ t('messages.accountingCash') }}</option></Select>
                                        </FormField>
                                        <FormField label-key="paymentTermsDays" :error="fieldError('default_payment_terms_days')"><Input v-model="form.default_payment_terms_days" type="number" min="0" /></FormField>
                                        <FormField label-key="fiscalYearStartMonth" :error="fieldError('fiscal_year_start_month')"><Input v-model="form.fiscal_year_start_month" type="number" min="1" max="12" /></FormField>
                                        <FormField label-key="fiscalYearStartDay" :error="fieldError('fiscal_year_start_day')"><Input v-model="form.fiscal_year_start_day" type="number" min="1" max="31" /></FormField>
                                    </div>
                                </FormSection>
                                <FormSection title-key="regionalFormats">
                                    <div class="form-grid">
                                        <FormField label-key="timezone" required :error="fieldError('timezone')"><Input v-model="form.timezone" /></FormField>
                                        <FormField label-key="language" :error="fieldError('language')"><Input v-model="form.language" /></FormField>
                                        <FormField label-key="dateFormat" :error="fieldError('date_format')"><Input v-model="form.date_format" /></FormField>
                                        <FormField label-key="timeFormat" :error="fieldError('time_format')"><Input v-model="form.time_format" /></FormField>
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'modules'" class="form-tab-panel">
                                <FormSection title-key="enabledModules" description-key="enabledModules">
                                    <div class="form-toggle-grid">
                                        <FormToggle v-model="form.enable_pos" label-key="enablePosCompany" />
                                        <FormToggle v-model="form.enable_inventory" label-key="enableInventoryCompany" />
                                        <FormToggle v-model="form.enable_accounting" label-key="enableAccounting" />
                                        <FormToggle v-model="form.enable_hr" label-key="enableHr" />
                                        <FormToggle v-model="form.enable_crm" label-key="enableCrm" />
                                        <FormToggle v-model="form.enable_ecommerce" label-key="enableEcommerce" />
                                    </div>
                                </FormSection>
                            </div>

                            <div v-show="active === 'subscription'" class="form-tab-panel">
                                <FormSection title-key="subscriptionPlanSection">
                                    <FormField label-key="subscriptionPlanId" :error="fieldError('subscription_plan_id')"><Input v-model="form.subscription_plan_id" /></FormField>
                                    <div class="form-grid-3 mt-4">
                                        <FormField label-key="startDate" :error="fieldError('subscription_start_at')"><Input v-model="form.subscription_start_at" type="date" /></FormField>
                                        <FormField label-key="endDate" :error="fieldError('subscription_end_at')"><Input v-model="form.subscription_end_at" type="date" /></FormField>
                                        <FormField label-key="trialEnds" :error="fieldError('trial_ends_at')"><Input v-model="form.trial_ends_at" type="date" /></FormField>
                                    </div>
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
                                                <option value="trial">{{ t('filters.trial') }}</option>
                                            </Select>
                                        </FormField>
                                        <div class="flex items-end"><FormToggle v-model="form.is_active" label-key="activeFlagCompany" /></div>
                                    </div>
                                </FormSection>
                                <FormSection v-if="company" title-key="auditTrail">
                                    <div class="form-audit-grid">
                                        <div><p class="form-audit-label">{{ t('fields.created') }}</p><p class="form-audit-value">{{ company.created_at }}</p></div>
                                        <div><p class="form-audit-label">{{ t('fields.lastUpdated') }}</p><p class="form-audit-value">{{ company.updated_at }}</p></div>
                                    </div>
                                </FormSection>
                            </div>
                        </template>
                    </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.companies.index')"
                    :submit-label="isEdit ? submit('saveCompany') : submit('createCompany')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
