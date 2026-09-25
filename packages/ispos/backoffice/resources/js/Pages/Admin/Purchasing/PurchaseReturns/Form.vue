<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import {
    defaultPurchaseReturnForm,
    defaultPurchaseReturnLine,
    type PurchaseReturnFormData,
    type PurchaseReturnRecord,
} from '@/types/purchaseReturn';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const { can } = usePermissions();
const page = useModulePage('purchaseReturns');
const { t, submit } = useLocale();

const props = defineProps<{
    purchaseReturn: PurchaseReturnRecord | null;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    suppliers: Array<{ id: string; name: string; supplier_code: string }>;
    products: Array<{ id: string; sku: string; name: string }>;
}>();

const isEdit = computed(() => !!props.purchaseReturn);
const isDraft = computed(() => !props.purchaseReturn || props.purchaseReturn.status === 'draft');
const pageTitle = computed(() => (isEdit.value ? props.purchaseReturn!.return_number : 'New purchase return'));

const form = useForm<PurchaseReturnFormData>(
    defaultPurchaseReturnForm(props.purchaseReturn, props.stores[0]?.id ?? '', props.suppliers[0]?.id ?? ''),
);

function addLine() {
    form.lines.push(defaultPurchaseReturnLine());
}

function removeLine(index: number) {
    if (form.lines.length > 1) form.lines.splice(index, 1);
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.return'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.purchase-returns.update', props.purchaseReturn!.id));
    } else {
        form.post(route('admin.purchase-returns.store'));
    }
}

function postReturn() {
    router.post(route('admin.purchase-returns.post', props.purchaseReturn!.id));
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="purchaseReturns"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.purchase-returns.index')"
        >
            <template v-if="isEdit && purchaseReturn" #meta>
                <Badge :variant="purchaseReturn.status === 'posted' ? 'success' : 'neutral'">{{ purchaseReturn.status }}</Badge>
            </template>

            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-6">
                    <FormSection title-key="purchaseReturnDetails" description-key="purchaseReturnDetails">
                        <div class="form-grid">
                            <FormField label-key="store" required :error="form.errors.store_id">
                                <Select v-model="form.store_id" :disabled="!isDraft || isEdit">
                                    <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.store_name }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="supplier" required :error="form.errors.supplier_id">
                                <Select v-model="form.supplier_id" :disabled="!isDraft || isEdit">
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="reason" required class="md:col-span-2" :error="form.errors.reason">
                                <Input v-model="form.reason" :disabled="!isDraft" />
                            </FormField>
                            <FormField label-key="notes" class="md:col-span-2" :error="form.errors.notes">
                                <FormTextarea v-model="form.notes" :rows="2" :disabled="!isDraft" />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title-key="transferLines">
                        <div class="space-y-3">
                            <div v-for="(line, index) in form.lines" :key="index" class="grid gap-2 rounded-lg border border-border p-3 md:grid-cols-12">
                                <FormField label-key="product" class="md:col-span-5" :error="form.errors[`lines.${index}.product_id`]">
                                    <Select v-model="line.product_id" :disabled="!isDraft">
                                        <option value="">{{ t('fields.product') }}</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.sku }} — {{ product.name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField label-key="qty" class="md:col-span-2" :error="form.errors[`lines.${index}.qty`]">
                                    <Input v-model="line.qty" type="number" min="0" step="any" :disabled="!isDraft" />
                                </FormField>
                                <FormField label-key="unitCost" class="md:col-span-2" :error="form.errors[`lines.${index}.unit_cost`]">
                                    <Input v-model="line.unit_cost" type="number" min="0" step="any" :disabled="!isDraft" />
                                </FormField>
                                <div v-if="isDraft" class="flex items-end md:col-span-3">
                                    <Button type="button" variant="ghost" @click="removeLine(index)">{{ t('forms.actions.removeLine') }}</Button>
                                </div>
                            </div>
                            <Button v-if="isDraft" type="button" variant="secondary" @click="addLine">{{ t('forms.actions.addLine') }}</Button>
                        </div>
                    </FormSection>

                    <div v-if="isEdit && purchaseReturn?.status === 'draft' && can('purchasing.approve')" class="flex gap-2">
                        <Button type="button" @click="postReturn">{{ t('forms.actions.postReturn') }}</Button>
                    </div>
                </div>

                <FormActionBar
                    v-if="isDraft"
                    :cancel-href="route('admin.purchase-returns.index')"
                    :submit-label="isEdit ? submit('saveReturn') : submit('createReturn')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
