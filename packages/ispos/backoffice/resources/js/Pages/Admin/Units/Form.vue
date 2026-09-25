<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { defaultUnitForm, type UnitFormData, type UnitRecord } from '@/types/unit';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('units');
const { t, placeholder, submit } = useLocale();

const props = defineProps<{
    unit: UnitRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.unit);
const pageTitle = useFormPageTitle('unit', isEdit);

const form = useForm<UnitFormData>(defaultUnitForm(props.unit, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.unit'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.units.update', props.unit!.id));
    } else {
        form.post(route('admin.units.store'));
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
            subtitle-key="units"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.units.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="unitDetails" description-key="unitDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.unit_code">
                                <Input v-model="form.unit_code" :placeholder="placeholder('codePc')" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="symbol" :error="form.errors.symbol">
                                <Input v-model="form.symbol" :placeholder="placeholder('symbolPc')" />
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                        </div>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.units.index')"
                    :submit-label="isEdit ? submit('saveUnit') : submit('createUnit')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
