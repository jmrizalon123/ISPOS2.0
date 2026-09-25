<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { defaultPriceGroupForm, type PriceGroupFormData, type PriceGroupRecord } from '@/types/priceGroup';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('priceGroups');
const { t, submit } = useLocale();

const props = defineProps<{
    priceGroup: PriceGroupRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.priceGroup);
const pageTitle = useFormPageTitle('priceGroup', isEdit);

const form = useForm<PriceGroupFormData>(defaultPriceGroupForm(props.priceGroup, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.priceGroup'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.price-groups.update', props.priceGroup!.id));
    } else {
        form.post(route('admin.price-groups.store'));
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
            subtitle-key="priceGroups"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.price-groups.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="priceGroupDetails" description-key="priceGroupDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.group_code">
                                <Input v-model="form.group_code" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                        </div>
                        <FormField label-key="description" class="mt-4" :error="form.errors.description">
                            <FormTextarea v-model="form.description" />
                        </FormField>
                        <FormToggle v-model="form.is_default" class="mt-4" label-key="defaultPriceGroup" />
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.price-groups.index')"
                    :submit-label="isEdit ? submit('savePriceGroup') : submit('createPriceGroup')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
