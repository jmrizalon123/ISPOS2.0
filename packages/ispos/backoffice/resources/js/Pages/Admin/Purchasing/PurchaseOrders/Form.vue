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
    defaultPurchaseOrderForm,
    defaultPurchaseOrderLine,
    type PurchaseOrderFormData,
    type PurchaseOrderRecord,
} from '@/types/purchaseOrder';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const { can } = usePermissions();
const page = useModulePage('purchaseOrders');
const { t, submit } = useLocale();

const props = defineProps<{
    purchaseOrder: PurchaseOrderRecord | null;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    suppliers: Array<{ id: string; name: string; supplier_code: string }>;
    products: Array<{ id: string; sku: string; name: string }>;
}>();

const isEdit = computed(() => !!props.purchaseOrder);
const isDraft = computed(() => !props.purchaseOrder || props.purchaseOrder.status === 'draft');
const pageTitle = computed(() => (isEdit.value ? props.purchaseOrder!.po_number : 'New purchase order'));

const form = useForm<PurchaseOrderFormData>(
    defaultPurchaseOrderForm(props.purchaseOrder, props.stores[0]?.id ?? '', props.suppliers[0]?.id ?? ''),
);

function addLine() {
    form.lines.push(defaultPurchaseOrderLine());
}

function removeLine(index: number) {
    if (form.lines.length > 1) form.lines.splice(index, 1);
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.po'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.purchase-orders.update', props.purchaseOrder!.id));
    } else {
        form.post(route('admin.purchase-orders.store'));
    }
}

function approvePo() {
    router.post(route('admin.purchase-orders.approve', props.purchaseOrder!.id));
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="purchaseOrders"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.purchase-orders.index')"
        >
            <template v-if="isEdit && purchaseOrder" #meta>
                <Badge :variant="purchaseOrder.status === 'received' ? 'success' : 'neutral'">
                    {{ purchaseOrder.status.replace(/_/g, ' ') }}
                </Badge>
            </template>

            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-6">
                    <FormSection title-key="purchaseOrderDetails" description-key="purchaseOrderDetails">
                        <div class="form-grid">
                            <FormField label-key="store" required :error="form.errors.store_id">
                                <Select v-model="form.store_id" :disabled="!isDraft">
                                    <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.store_name }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="supplier" required :error="form.errors.supplier_id">
                                <Select v-model="form.supplier_id" :disabled="!isDraft">
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }} ({{ supplier.supplier_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="orderDate" required :error="form.errors.order_date">
                                <Input v-model="form.order_date" type="date" :disabled="!isDraft" />
                            </FormField>
                            <FormField label-key="expectedDate" :error="form.errors.expected_date">
                                <Input v-model="form.expected_date" type="date" :disabled="!isDraft" />
                            </FormField>
                            <FormField label-key="notes" class="md:col-span-2" :error="form.errors.notes">
                                <FormTextarea v-model="form.notes" :rows="2" :disabled="!isDraft" />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title-key="transferLines">
                        <div class="space-y-3">
                            <div v-for="(line, index) in form.lines" :key="index" class="grid gap-2 rounded-lg border border-border p-3 md:grid-cols-12">
                                <FormField label-key="product" class="md:col-span-4" :error="form.errors[`lines.${index}.product_id`]">
                                    <Select v-model="line.product_id" :disabled="!isDraft">
                                        <option value="">{{ t('fields.product') }}</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.sku }} — {{ product.name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField label-key="qty" class="md:col-span-2" :error="form.errors[`lines.${index}.ordered_qty`]">
                                    <Input v-model="line.ordered_qty" type="number" min="0" step="any" :disabled="!isDraft" />
                                </FormField>
                                <FormField label-key="unitCost" class="md:col-span-2" :error="form.errors[`lines.${index}.unit_cost`]">
                                    <Input v-model="line.unit_cost" type="number" min="0" step="any" :disabled="!isDraft" />
                                </FormField>
                                <FormField label-key="notes" class="md:col-span-3" :error="form.errors[`lines.${index}.notes`]">
                                    <Input v-model="line.notes" :disabled="!isDraft" />
                                </FormField>
                                <div v-if="isDraft" class="flex items-end md:col-span-1">
                                    <Button type="button" variant="ghost" @click="removeLine(index)">{{ t('forms.actions.removeLine') }}</Button>
                                </div>
                                <div v-if="isEdit && purchaseOrder?.lines?.[index]" class="md:col-span-12 text-xs text-ink-muted">
                                    {{ t('columns.received') }}: {{ purchaseOrder.lines[index].received_qty ?? 0 }} / {{ purchaseOrder.lines[index].ordered_qty }}
                                </div>
                            </div>
                            <Button v-if="isDraft" type="button" variant="secondary" @click="addLine">{{ t('forms.actions.addLine') }}</Button>
                        </div>
                    </FormSection>

                    <div v-if="isEdit && purchaseOrder" class="flex flex-wrap gap-2">
                        <Button v-if="isDraft && can('purchasing.approve')" type="button" @click="approvePo">{{ t('forms.actions.approvePo') }}</Button>
                        <Link v-if="['approved', 'partially_received'].includes(purchaseOrder.status) && can('purchasing.create')" :href="route('admin.purchase-orders.receive.create', purchaseOrder.id)">
                            <Button type="button">{{ submit('receiveGoods') }}</Button>
                        </Link>
                    </div>
                </div>

                <FormActionBar
                    v-if="isDraft"
                    :cancel-href="route('admin.purchase-orders.index')"
                    :submit-label="isEdit ? submit('savePo') : submit('createPo')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
