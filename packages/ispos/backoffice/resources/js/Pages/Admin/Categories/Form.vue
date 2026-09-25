<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { defaultCategoryForm, type CategoryFormData, type CategoryRecord } from '@/types/category';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('categories');
const { t, placeholder, submit } = useLocale();

const props = defineProps<{
    category: CategoryRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    parents: Array<{ id: string; name: string; category_code: string; company_id: string }>;
}>();

const isEdit = computed(() => !!props.category);
const pageTitle = useFormPageTitle('category', isEdit);

const form = useForm<CategoryFormData>(defaultCategoryForm(props.category, props.companies[0]?.id ?? ''));

const filteredParents = computed(() =>
    props.parents.filter((p) => !form.company_id || p.company_id === form.company_id),
);

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        isEdit.value ? t('entities.category') : t('entities.category'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.categories.update', props.category!.id));
    } else {
        form.post(route('admin.categories.store'));
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
            subtitle-key="categories"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.categories.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="categoryDetails" description-key="categoryDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.category_code">
                                <Input v-model="form.category_code" :placeholder="placeholder('codeBev')" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="parent" :error="form.errors.parent_id">
                                <Select v-model="form.parent_id">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="parent in filteredParents" :key="parent.id" :value="parent.id">
                                        {{ parent.name }} ({{ parent.category_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="sortOrder" :error="form.errors.sort_order">
                                <Input v-model="form.sort_order" type="number" min="0" />
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
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.categories.index')"
                    :submit-label="isEdit ? submit('saveCategory') : submit('createCategory')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
