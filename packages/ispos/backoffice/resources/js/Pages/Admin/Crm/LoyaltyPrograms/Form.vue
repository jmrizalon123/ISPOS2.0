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
import { defaultLoyaltyProgramForm, type LoyaltyProgramFormData, type LoyaltyProgramRecord } from '@/types/loyaltyProgram';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('loyaltyPrograms');
const { t, submit } = useLocale();

const props = defineProps<{
    loyaltyProgram: LoyaltyProgramRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.loyaltyProgram);
const pageTitle = useFormPageTitle('loyaltyProgram', isEdit);

const form = useForm<LoyaltyProgramFormData>(defaultLoyaltyProgramForm(props.loyaltyProgram, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.loyaltyProgram'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.loyalty-programs.update', props.loyaltyProgram!.id));
    } else {
        form.post(route('admin.loyalty-programs.store'));
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
            subtitle-key="loyaltyPrograms"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.loyalty-programs.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="loyaltyDetails" description-key="loyaltyDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.program_code">
                                <Input v-model="form.program_code" />
                            </FormField>
                            <FormField label-key="name" required :error="form.errors.name">
                                <Input v-model="form.name" />
                            </FormField>
                            <FormField label-key="earnRate" required hint-key="earnRate" :error="form.errors.earn_rate">
                                <Input v-model="form.earn_rate" type="number" step="0.01" min="0" />
                            </FormField>
                            <FormField label-key="redeemValuePerPoint" required hint-key="redeemValuePerPoint" :error="form.errors.redeem_value_per_point">
                                <Input v-model="form.redeem_value_per_point" type="number" step="0.0001" min="0" />
                            </FormField>
                            <FormField label-key="minRedeemPoints" required :error="form.errors.min_redeem_points">
                                <Input v-model="form.min_redeem_points" type="number" step="1" min="0" />
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                        </div>
                        <FormToggle v-model="form.is_default" class="mt-4" label-key="defaultLoyaltyProgram" />
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.loyalty-programs.index')"
                    :submit-label="isEdit ? submit('saveProgram') : submit('createProgram')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
