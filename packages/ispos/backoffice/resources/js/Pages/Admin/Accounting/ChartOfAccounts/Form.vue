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
import { defaultChartOfAccountForm, type ChartOfAccountFormData, type ChartOfAccountRecord } from '@/types/chartOfAccount';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('chartOfAccounts');
const { t, submit } = useLocale();

const props = defineProps<{
    account: ChartOfAccountRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
}>();

const isEdit = computed(() => !!props.account);
const pageTitle = useFormPageTitle('account', isEdit);
const form = useForm<ChartOfAccountFormData>(defaultChartOfAccountForm(props.account, props.companies[0]?.id ?? ''));

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.account'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.chart-of-accounts.update', props.account!.id));
    } else {
        form.post(route('admin.chart-of-accounts.store'));
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
            subtitle-key="chartOfAccounts"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.chart-of-accounts.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="accountDetails" description-key="accountDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id" :disabled="isEdit">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="accountCode" required :error="form.errors.account_code">
                                <Input v-model="form.account_code" :disabled="!!account?.is_system" />
                            </FormField>
                            <FormField label-key="accountName" required :error="form.errors.account_name">
                                <Input v-model="form.account_name" />
                            </FormField>
                            <FormField label-key="accountType" required :error="form.errors.account_type">
                                <Select v-model="form.account_type">
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                </Select>
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
                    :cancel-href="route('admin.chart-of-accounts.index')"
                    :submit-label="isEdit ? submit('saveAccount') : submit('createAccount')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
