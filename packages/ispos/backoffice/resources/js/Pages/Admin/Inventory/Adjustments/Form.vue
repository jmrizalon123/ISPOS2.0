<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const page = useModulePage('adjustments');
const { t, placeholder, submit } = useLocale();

defineProps<{
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    products: Array<{ id: string; sku: string; name: string }>;
}>();

const form = useForm({
    store_id: '',
    product_id: '',
    quantity_delta: '',
    reason: '',
    notes: '',
});

function submitForm() {
    form.post(route('admin.inventory.adjustments.store'));
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <FormShell
            :title="page.header"
            subtitle-key="adjustments"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.inventory.stock.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="adjustmentDetails" description-key="adjustmentDetails">
                        <div class="form-grid">
                            <FormField label-key="store" required :error="form.errors.store_id">
                                <Select v-model="form.store_id" required>
                                    <option value="" disabled>{{ t('fields.selectStore') }}</option>
                                    <option v-for="store in stores" :key="store.id" :value="store.id">
                                        {{ store.store_name }} ({{ store.store_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="product" required :error="form.errors.product_id">
                                <Select v-model="form.product_id" required>
                                    <option value="" disabled>{{ t('fields.product') }}</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }} ({{ product.sku }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="quantityChange" required hint-key="adjustmentQty" :error="form.errors.quantity_delta">
                                <Input v-model="form.quantity_delta" type="number" step="0.0001" required />
                            </FormField>
                            <FormField label-key="reason" required :error="form.errors.reason">
                                <Input v-model="form.reason" :placeholder="placeholder('adjustmentReason')" required />
                            </FormField>
                            <FormField label-key="notes" class="md:col-span-2" :error="form.errors.notes">
                                <Input v-model="form.notes" :placeholder="placeholder('additionalDetails')" />
                            </FormField>
                        </div>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.inventory.stock.index')"
                    :submit-label="submit('saveAdjustment')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
