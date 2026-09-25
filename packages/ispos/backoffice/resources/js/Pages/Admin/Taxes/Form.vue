<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { defaultTaxForm, type TaxFormData, type TaxRecord } from '@/types/tax';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('taxes');
const { t, placeholder, submit } = useLocale();

const props = defineProps<{
    tax: TaxRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.tax);
const pageTitle = useFormPageTitle('tax', isEdit);

const form = useForm<TaxFormData>(defaultTaxForm(props.tax, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.tax'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.taxes.update', props.tax!.id));
    } else {
        form.post(route('admin.taxes.store'));
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
            subtitle-key="taxes"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.taxes.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="taxDetails" description-key="taxDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.tax_code">
                                <Input v-model="form.tax_code" :placeholder="placeholder('codeVat')" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="rate" required :error="form.errors.rate">
                                <Input v-model="form.rate" type="number" step="0.01" min="0" max="100" />
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                        </div>
                        <FormToggle v-model="form.is_inclusive" class="mt-4" label-key="taxInclusive" />
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.taxes.index')"
                    :submit-label="isEdit ? submit('saveTax') : submit('createTax')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
