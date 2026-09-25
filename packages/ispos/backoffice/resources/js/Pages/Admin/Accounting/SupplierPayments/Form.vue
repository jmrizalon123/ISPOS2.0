<script setup lang="ts">
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import { confirmSave } from '@/Composables/useConfirm';
import { formatReportMoney } from '@/Composables/useReportFilters';
import { billBalance, defaultSupplierPaymentForm } from '@/types/supplierPayment';
import type { OpenVendorBillRecord } from '@/types/vendorBill';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const page = useModulePage('supplierPayments');
const { t, submit } = useLocale();

const props = defineProps<{
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    suppliers: Array<{ id: string; name: string; company_id: string }>;
    openBills: OpenVendorBillRecord[];
    filters: { company_id: string; supplier_id: string };
}>();

const defaultCompanyId = props.filters.company_id || props.companies[0]?.id || props.suppliers[0]?.company_id || '';

const form = useForm(defaultSupplierPaymentForm(defaultCompanyId, props.filters.supplier_id, props.openBills));

const filteredSuppliers = computed(() =>
    props.suppliers.filter((supplier) => !form.company_id || supplier.company_id === form.company_id),
);

const allocationTotal = computed(() =>
    form.allocations.reduce((sum, row) => sum + (Number(row.amount) || 0), 0).toFixed(4),
);

watch(
    () => form.supplier_id,
    (supplierId) => {
        router.get(
            route('admin.supplier-payments.create'),
            { company_id: form.company_id, supplier_id: supplierId },
            { preserveState: true, replace: true },
        );
    },
);

watch(
    () => props.openBills,
    (bills) => {
        form.allocations = bills.map((bill) => ({
            vendor_bill_id: bill.id,
            amount: billBalance(bill),
        }));
        form.amount = allocationTotal.value;
    },
);

watch(allocationTotal, (total) => {
    form.amount = total;
});

async function submitForm() {
    if (!props.openBills.length) return;
    const confirmed = await confirmSave(t('common.create'), t('entities.payment'));
    if (!confirmed) return;
    form.post(route('admin.supplier-payments.store'));
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ t('subheaders.recordPayment') }}</template>

        <FormShell
            :title="t('subheaders.recordPayment')"
            subtitle-key="supplierPayments"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.supplier-payments.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-6">
                    <FormSection title-key="paymentDetails" description-key="paymentDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="supplier" required :error="form.errors.supplier_id">
                                <Select v-model="form.supplier_id">
                                    <option value="">{{ t('fields.selectSupplier') }}</option>
                                    <option v-for="supplier in filteredSuppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="paymentDate" required :error="form.errors.payment_date">
                                <Input v-model="form.payment_date" type="date" />
                            </FormField>
                            <FormField label-key="paymentMethod" required :error="form.errors.payment_method">
                                <Select v-model="form.payment_method">
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="bank_transfer">Bank transfer</option>
                                </Select>
                            </FormField>
                            <FormField label-key="reference" :error="form.errors.reference">
                                <Input v-model="form.reference" />
                            </FormField>
                            <FormField label-key="amount" required :error="form.errors.amount">
                                <Input v-model="form.amount" type="number" step="0.0001" min="0" readonly />
                            </FormField>
                        </div>
                        <FormField label-key="notes" :error="form.errors.notes">
                            <FormTextarea v-model="form.notes" :rows="2" />
                        </FormField>
                    </FormSection>

                    <FormSection v-if="openBills.length" :title="t('fields.vendorBill')">
                        <div class="space-y-3">
                            <div
                                v-for="(allocation, index) in form.allocations"
                                :key="allocation.vendor_bill_id"
                                class="grid gap-3 rounded-lg border border-border p-3 md:grid-cols-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ openBills.find((b) => b.id === allocation.vendor_bill_id)?.bill_number }}
                                    </p>
                                    <p class="text-xs text-ink-muted">
                                        {{ t('columns.balance') }}
                                        {{
                                            formatReportMoney(
                                                billBalance(openBills.find((b) => b.id === allocation.vendor_bill_id)!),
                                            )
                                        }}
                                    </p>
                                </div>
                                <FormField label-key="applyAmount" :error="form.errors[`allocations.${index}.amount`]">
                                    <Input v-model="allocation.amount" type="number" step="0.0001" min="0" />
                                </FormField>
                            </div>
                        </div>
                    </FormSection>
                    <p v-else-if="form.supplier_id" class="text-sm text-ink-muted">{{ t('common.noResults') }}</p>
                </div>

                <FormActionBar
                    :processing="form.processing"
                    :cancel-href="route('admin.supplier-payments.index')"
                    :submit-label="submit('savePayment')"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
