<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { defaultSupplierForm, type SupplierFormData, type SupplierRecord } from '@/types/supplier';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('suppliers');
const { t, submit } = useLocale();

const props = defineProps<{
    supplier: SupplierRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.supplier);
const pageTitle = useFormPageTitle('supplier', isEdit);
const form = useForm<SupplierFormData>(defaultSupplierForm(props.supplier, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.supplier'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.suppliers.update', props.supplier!.id));
    } else {
        form.post(route('admin.suppliers.store'));
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
            subtitle-key="suppliers"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.suppliers.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="supplierDetails" description-key="supplierDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.supplier_code">
                                <Input v-model="form.supplier_code" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="contactName" :error="form.errors.contact_name">
                                <Input v-model="form.contact_name" />
                            </FormField>
                            <FormField label-key="email" :error="form.errors.email">
                                <Input v-model="form.email" type="email" />
                            </FormField>
                            <FormField label-key="phone" :error="form.errors.phone">
                                <Input v-model="form.phone" />
                            </FormField>
                            <FormField label-key="paymentTerms" :error="form.errors.payment_terms">
                                <Input v-model="form.payment_terms" />
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="address" class="md:col-span-2" :error="form.errors.address">
                                <FormTextarea v-model="form.address" :rows="2" />
                            </FormField>
                            <FormField label-key="notes" class="md:col-span-2" :error="form.errors.notes">
                                <FormTextarea v-model="form.notes" :rows="2" />
                            </FormField>
                        </div>
                    </FormSection>
                </div>
                <FormActionBar
                    :cancel-href="route('admin.suppliers.index')"
                    :submit-label="isEdit ? submit('saveSupplier') : submit('createSupplier')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
