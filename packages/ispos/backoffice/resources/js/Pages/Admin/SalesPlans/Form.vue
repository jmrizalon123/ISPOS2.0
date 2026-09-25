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
import { defaultSalesPlanForm, type SalesPlanFormData, type SalesPlanRecord, type SalesPlanStoreOption } from '@/types/salesPlan';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const page = useModulePage('salesPlans');
const { t, placeholder, submit } = useLocale();

const props = defineProps<{
    salesPlan: SalesPlanRecord | null;
    companies: Array<{ id: string; name: string; company_code: string; display_name?: string | null }>;
    stores: SalesPlanStoreOption[];
}>();

const isEdit = computed(() => !!props.salesPlan);
const pageTitle = useFormPageTitle('salesPlan', isEdit);

const form = useForm<SalesPlanFormData>(defaultSalesPlanForm(props.salesPlan, props.companies[0]?.id ?? ''));

const companyStores = computed(() =>
    props.stores.filter((store) => !form.company_id || store.company_id === form.company_id),
);

watch(
    () => form.company_id,
    () => {
        form.store_ids = form.store_ids.filter((id) => companyStores.value.some((store) => store.id === id));
    },
);

function toggleStore(id: string) {
    form.store_ids = form.store_ids.includes(id)
        ? form.store_ids.filter((item) => item !== id)
        : [...form.store_ids, id];
}

function otherPlanName(store: SalesPlanStoreOption): string | null {
    if (!store.sales_plan_id || store.sales_plan_id === props.salesPlan?.id) {
        return null;
    }

    return store.sales_plan?.name ?? t('fields.salesPlan');
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.salesPlan'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.sales-plans.update', props.salesPlan!.id));
    } else {
        form.post(route('admin.sales-plans.store'));
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
            subtitle-key="salesPlans"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.sales-plans.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="salesPlanDetails" description-key="salesPlanDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="planCode" required :error="form.errors.plan_code">
                                <Input v-model="form.plan_code" :placeholder="placeholder('codePlan')" />
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
                    </FormSection>

                    <FormSection title-key="assignedStores" description-key="assignedStores">
                        <p v-if="!companyStores.length" class="text-sm text-ink-muted">
                            {{ t('hints.noStoresForSalesPlan') }}
                        </p>
                        <div v-else class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="store in companyStores"
                                :key="store.id"
                                class="flex items-start gap-2 rounded-lg border border-line px-3 py-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.store_ids.includes(store.id)"
                                    class="mt-0.5 rounded border-line text-accent focus:ring-accent"
                                    @change="toggleStore(store.id)"
                                />
                                <span>
                                    <span class="block font-medium">{{ store.store_name }}</span>
                                    <span class="block text-xs text-ink-muted">
                                        {{ store.store_code }}
                                        <template v-if="store.store_category"> · {{ store.store_category }}</template>
                                    </span>
                                    <span v-if="otherPlanName(store)" class="mt-0.5 block text-xs text-ink-muted">
                                        {{ t('hints.assignedToOtherPlan', { plan: otherPlanName(store) }) }}
                                    </span>
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.store_ids" class="mt-2 text-sm text-danger">{{ form.errors.store_ids }}</p>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.sales-plans.index')"
                    :submit-label="isEdit ? submit('saveSalesPlan') : submit('createSalesPlan')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
