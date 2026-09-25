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
import { defaultMembershipPlanForm, type MembershipPlanFormData, type MembershipPlanRecord } from '@/types/membershipPlan';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('membershipPlans');
const { t, submit } = useLocale();

const props = defineProps<{
    membershipPlan: MembershipPlanRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    priceGroups: Array<{ id: string; name: string; group_code: string }>;
}>();

const isEdit = computed(() => !!props.membershipPlan);
const pageTitle = useFormPageTitle('membershipPlan', isEdit);

const form = useForm<MembershipPlanFormData>(defaultMembershipPlanForm(props.membershipPlan, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.membershipPlan'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.membership-plans.update', props.membershipPlan!.id));
    } else {
        form.post(route('admin.membership-plans.store'));
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
            subtitle-key="membershipPlans"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.membership-plans.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="membershipDetails" description-key="membershipDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.plan_code">
                                <Input v-model="form.plan_code" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="price" :error="form.errors.price">
                                <Input v-model="form.price" type="number" step="0.01" min="0" :placeholder="t('fields.optional')" />
                            </FormField>
                            <FormField label-key="durationDays" :error="form.errors.duration_days">
                                <Input v-model="form.duration_days" type="number" step="1" min="1" :placeholder="t('fields.optional')" />
                            </FormField>
                            <FormField label-key="discountPercent" required :error="form.errors.discount_percent">
                                <Input v-model="form.discount_percent" type="number" step="0.01" min="0" max="100" />
                            </FormField>
                            <FormField label-key="priceGroup" :error="form.errors.price_group_id">
                                <Select v-model="form.price_group_id">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="group in priceGroups" :key="group.id" :value="group.id">
                                        {{ group.name }} ({{ group.group_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="description" class="md:col-span-2" :error="form.errors.description">
                                <FormTextarea v-model="form.description" :rows="2" />
                            </FormField>
                        </div>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.membership-plans.index')"
                    :submit-label="isEdit ? submit('savePlan') : submit('createPlan')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
