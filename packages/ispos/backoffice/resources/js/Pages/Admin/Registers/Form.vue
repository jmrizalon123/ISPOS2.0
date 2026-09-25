<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormMetaRow from '@/Components/forms/FormMetaRow.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import { defaultRegisterForm, type RegisterFormData, type RegisterRecord } from '@/types/register';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('registers');
const { t, field, submit, tab, tabDesc } = useLocale();

const props = defineProps<{
    register: RegisterRecord | null;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string }>;
    posDevices: Array<{ id: string; name: string; store_id: string; register_id: string }>;
}>();

const activeTab = ref('basic');
const isEdit = computed(() => !!props.register);
const pageTitle = useFormPageTitle('register', isEdit);

const tabs = computed(() => [
    { key: 'basic', label: tab('basic'), description: tabDesc('registerBasic') },
    { key: 'device', label: tab('device'), description: tabDesc('registerDevice') },
    { key: 'peripherals', label: tab('peripherals'), description: tabDesc('registerPeripherals') },
    { key: 'payments', label: tab('payments'), description: tabDesc('registerPayments') },
    { key: 'permissions', label: tab('permissions'), description: tabDesc('registerPermissions') },
    { key: 'features', label: tab('features'), description: tabDesc('registerFeatures') },
    { key: 'status', label: tab('status'), description: tabDesc('registerStatus') },
]);

const form = useForm<RegisterFormData>(
    defaultRegisterForm(props.register, props.register?.store_id ?? props.stores[0]?.id ?? ''),
);

const activeTabMeta = computed(() => tabs.value.find((item) => item.key === activeTab.value) ?? tabs.value[0]);

const tabErrorConfig: FormTabErrorConfig[] = [
    { key: 'basic', requiredFields: ['store_id', 'register_code', 'register_name'], errorPrefixes: ['min', 'permit_number', 'device_serial'] },
    { key: 'device', errorPrefixes: ['device_id', 'device_name', 'device_type', 'ip_address', 'mac_address', 'terminal_code', 'terminal_name'] },
    { key: 'peripherals', errorPrefixes: ['printer_id', 'cash_drawer_id', 'customer_display_id', 'kds_station_id', 'receipt_printer_', 'drawer_open_method'] },
    { key: 'payments', errorPrefixes: ['allow_cash_sales', 'allow_card_sales', 'allow_gcash_sales', 'allow_maya_sales', 'allow_other_payments', 'allow_discount'] },
    { key: 'permissions', errorPrefixes: ['allow_void', 'allow_refund', 'allow_reprint', 'allow_price_override', 'allow_open_drawer', 'require_cashier_login', 'require_manager_approval'] },
    { key: 'features', errorPrefixes: ['auto_print_', 'enable_', 'online_order_enabled', 'offline_mode_enabled', 'sync_enabled'] },
    { key: 'status', requiredFields: ['status'], errorPrefixes: ['is_active'] },
];

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

const filteredPosDevices = computed(() =>
    props.posDevices.filter((device) => device.store_id === form.store_id),
);

watch(
    () => form.store_id,
    () => {
        if (form.device_id && !filteredPosDevices.value.some((device) => device.id === form.device_id)) {
            form.device_id = '';
        }
    },
);

function fieldError(key: keyof RegisterFormData): string | undefined {
    return form.errors[key];
}

function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.register'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.registers.update', props.register!.id));
    } else {
        form.post(route('admin.registers.store'));
    }
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="registers"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.registers.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                    <template #default="{ active }">
                        <div v-show="active === 'basic'" class="form-tab-panel">
                            <FormMetaRow
                                v-if="register"
                                :items="[
                                    { label: field('recordId'), value: register.id },
                                    { label: field('uuid'), value: register.uuid ?? '—' },
                                ]"
                            />

                            <FormSection title-key="registerDetails" description-key="registerDetails">
                                <FormField label-key="store" required :error="fieldError('store_id')">
                                    <Select v-model="form.store_id">
                                        <option v-for="store in stores" :key="store.id" :value="store.id">
                                            {{ store.store_name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <div class="form-grid mt-4">
                                    <FormField label-key="registerCode" required :error="fieldError('register_code')">
                                        <Input v-model="form.register_code" />
                                    </FormField>
                                    <FormField label-key="registerName" required :error="fieldError('register_name')">
                                        <Input v-model="form.register_name" />
                                    </FormField>
                                    <FormField label-key="terminalCode" :error="fieldError('terminal_code')">
                                        <Input v-model="form.terminal_code" />
                                    </FormField>
                                    <FormField label-key="terminalName" :error="fieldError('terminal_name')">
                                        <Input v-model="form.terminal_name" />
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection title-key="registerCompliance" description-key="registerCompliance">
                                <div class="form-grid">
                                    <FormField label-key="min" :error="fieldError('min')">
                                        <Input v-model="form.min" />
                                    </FormField>
                                    <FormField label-key="permitNumber" :error="fieldError('permit_number')">
                                        <Input v-model="form.permit_number" />
                                    </FormField>
                                    <FormField label-key="deviceSerial" :error="fieldError('device_serial')">
                                        <Input v-model="form.device_serial" readonly class="bg-surface-muted" />
                                    </FormField>
                                    <div class="flex items-end">
                                        <FormToggle v-model="form.reset_registration" label-key="resetRegistration" />
                                    </div>
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'device'" class="form-tab-panel">
                            <FormSection title-key="networkDevice" description-key="networkDevice">
                                <FormField label-key="linkedPosDevice" :error="fieldError('device_id')">
                                    <Select v-model="form.device_id">
                                        <option value="">{{ field('selectPosDevice') }}</option>
                                        <option v-for="device in filteredPosDevices" :key="device.id" :value="device.id">
                                            {{ device.name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <div class="form-grid mt-4">
                                    <FormField label-key="deviceName" :error="fieldError('device_name')">
                                        <Input v-model="form.device_name" />
                                    </FormField>
                                    <FormField label-key="deviceType" :error="fieldError('device_type')">
                                        <Select v-model="form.device_type">
                                            <option value="">{{ field('selectType') }}</option>
                                            <option value="pos_terminal">{{ field('deviceTypePosTerminal') }}</option>
                                            <option value="tablet">{{ field('deviceTypeTablet') }}</option>
                                            <option value="mobile">{{ field('deviceTypeMobile') }}</option>
                                            <option value="kiosk">{{ field('deviceTypeKiosk') }}</option>
                                            <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                        </Select>
                                    </FormField>
                                    <FormField label-key="ipAddress" :error="fieldError('ip_address')">
                                        <Input v-model="form.ip_address" />
                                    </FormField>
                                    <FormField label-key="macAddress" :error="fieldError('mac_address')">
                                        <Input v-model="form.mac_address" />
                                    </FormField>
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'peripherals'" class="form-tab-panel">
                            <FormSection title-key="peripheralIds" description-key="peripheralIds">
                                <div class="form-grid">
                                    <FormField label-key="printerId" :error="fieldError('printer_id')">
                                        <Input v-model="form.printer_id" />
                                    </FormField>
                                    <FormField label-key="cashDrawerId" :error="fieldError('cash_drawer_id')">
                                        <Input v-model="form.cash_drawer_id" />
                                    </FormField>
                                    <FormField label-key="customerDisplayId" :error="fieldError('customer_display_id')">
                                        <Input v-model="form.customer_display_id" />
                                    </FormField>
                                    <FormField label-key="kdsStationId" :error="fieldError('kds_station_id')">
                                        <Input v-model="form.kds_station_id" />
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection title-key="receiptPrinter" description-key="receiptPrinter">
                                <div class="form-grid">
                                    <FormField label-key="receiptPrinterName" :error="fieldError('receipt_printer_name')">
                                        <Input v-model="form.receipt_printer_name" />
                                    </FormField>
                                    <FormField label-key="receiptPrinterType" :error="fieldError('receipt_printer_type')">
                                        <Select v-model="form.receipt_printer_type">
                                            <option value="">{{ field('selectType') }}</option>
                                            <option value="network">{{ field('printerTypeNetwork') }}</option>
                                            <option value="usb">{{ field('printerTypeUsb') }}</option>
                                            <option value="bluetooth">{{ field('printerTypeBluetooth') }}</option>
                                            <option value="serial">{{ field('printerTypeSerial') }}</option>
                                            <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                        </Select>
                                    </FormField>
                                    <FormField label-key="receiptPrinterIp" :error="fieldError('receipt_printer_ip')">
                                        <Input v-model="form.receipt_printer_ip" />
                                    </FormField>
                                    <FormField label-key="receiptPrinterPort" :error="fieldError('receipt_printer_port')">
                                        <Input v-model="form.receipt_printer_port" type="number" min="1" max="65535" />
                                    </FormField>
                                    <FormField label-key="drawerOpenMethod" :error="fieldError('drawer_open_method')">
                                        <Select v-model="form.drawer_open_method">
                                            <option value="">{{ field('selectType') }}</option>
                                            <option value="pulse">{{ field('drawerOpenPulse') }}</option>
                                            <option value="command">{{ field('drawerOpenCommand') }}</option>
                                            <option value="manual">{{ field('drawerOpenManual') }}</option>
                                            <option value="other">{{ t('messages.companyTypeOther') }}</option>
                                        </Select>
                                    </FormField>
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'payments'" class="form-tab-panel">
                            <FormSection title-key="paymentMethods" description-key="paymentMethods">
                                <div class="form-toggle-grid">
                                    <FormToggle v-model="form.allow_cash_sales" :label="field('allowCashSales')" />
                                    <FormToggle v-model="form.allow_card_sales" :label="field('allowCardSales')" />
                                    <FormToggle v-model="form.allow_gcash_sales" :label="field('allowGcashSales')" />
                                    <FormToggle v-model="form.allow_maya_sales" :label="field('allowMayaSales')" />
                                    <FormToggle v-model="form.allow_other_payments" :label="field('allowOtherPayments')" />
                                    <FormToggle v-model="form.allow_discount" :label="field('allowDiscount')" />
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'permissions'" class="form-tab-panel">
                            <FormSection title-key="registerPermissions" description-key="registerPermissions">
                                <div class="form-toggle-grid">
                                    <FormToggle v-model="form.allow_void" :label="field('allowVoid')" />
                                    <FormToggle v-model="form.allow_refund" :label="field('allowRefund')" />
                                    <FormToggle v-model="form.allow_reprint" :label="field('allowReprint')" />
                                    <FormToggle v-model="form.allow_price_override" :label="field('allowPriceOverride')" />
                                    <FormToggle v-model="form.allow_open_drawer" :label="field('allowOpenDrawer')" />
                                    <FormToggle v-model="form.require_cashier_login" :label="field('requireCashierLogin')" />
                                    <FormToggle v-model="form.require_manager_approval" :label="field('requireManagerApproval')" />
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'features'" class="form-tab-panel">
                            <FormSection title-key="printAndDisplay" description-key="printAndDisplay">
                                <div class="form-toggle-grid">
                                    <FormToggle v-model="form.auto_print_receipt" :label="field('autoPrintReceipt')" />
                                    <FormToggle v-model="form.auto_print_kitchen_order" :label="field('autoPrintKitchenOrder')" />
                                    <FormToggle v-model="form.auto_print_customer_receipt" :label="field('autoPrintCustomerReceipt')" />
                                    <FormToggle v-model="form.enable_customer_display" :label="field('enableCustomerDisplay')" />
                                    <FormToggle v-model="form.enable_kds" :label="field('enableKds')" />
                                    <FormToggle v-model="form.enable_ncs" :label="field('enableNcs')" />
                                </div>
                            </FormSection>

                            <FormSection title-key="syncChannels" description-key="syncChannels">
                                <div class="form-toggle-grid">
                                    <FormToggle v-model="form.online_order_enabled" :label="field('onlineOrderEnabled')" />
                                    <FormToggle v-model="form.offline_mode_enabled" :label="field('offlineModeEnabled')" />
                                    <FormToggle v-model="form.sync_enabled" :label="field('syncEnabled')" />
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'status'" class="form-tab-panel">
                            <FormSection title-key="registerLifecycle" description-key="registerLifecycle">
                                <div class="form-grid">
                                    <FormField label-key="status" required :error="fieldError('status')">
                                        <Select v-model="form.status">
                                            <option value="active">{{ t('common.active') }}</option>
                                            <option value="inactive">{{ t('common.inactive') }}</option>
                                        </Select>
                                    </FormField>
                                    <div class="flex items-end">
                                        <FormToggle v-model="form.is_active" label-key="activeFlag" />
                                    </div>
                                </div>
                            </FormSection>

                            <FormSection v-if="register" title-key="registerRuntime" description-key="registerRuntime">
                                <div class="form-audit-grid">
                                    <div>
                                        <p class="form-audit-label">{{ field('lastSyncAt') }}</p>
                                        <p class="form-audit-value">{{ formatDateTime(register.last_sync_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="form-audit-label">{{ field('lastZReadAt') }}</p>
                                        <p class="form-audit-value">{{ formatDateTime(register.last_z_read_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="form-audit-label">{{ field('currentCashier') }}</p>
                                        <p class="form-audit-value">{{ register.current_cashier?.name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="form-audit-label">{{ field('openedAt') }}</p>
                                        <p class="form-audit-value">{{ formatDateTime(register.opened_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="form-audit-label">{{ field('closedAt') }}</p>
                                        <p class="form-audit-value">{{ formatDateTime(register.closed_at) }}</p>
                                    </div>
                                </div>
                            </FormSection>

                            <FormSection v-if="register" title-key="auditTrail">
                                <div class="form-audit-grid">
                                    <div>
                                        <p class="form-audit-label">{{ field('created') }}</p>
                                        <p class="form-audit-value">{{ register.creator?.name ?? '—' }} · {{ formatDateTime(register.created_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="form-audit-label">{{ field('lastUpdated') }}</p>
                                        <p class="form-audit-value">{{ register.updater?.name ?? '—' }} · {{ formatDateTime(register.updated_at) }}</p>
                                    </div>
                                </div>
                            </FormSection>
                        </div>
                    </template>
                </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.registers.index')"
                    :submit-label="isEdit ? submit('saveRegister') : submit('createRegister')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
