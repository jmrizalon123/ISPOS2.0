<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import type { PurchaseOrderRecord } from '@/types/purchaseOrder';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('purchaseOrders');
const { t, field, submit } = useLocale();

const props = defineProps<{ purchaseOrder: PurchaseOrderRecord }>();

interface ReceiptForm {
    receipts: Array<{ line_id: string; receive_qty: number | string }>;
}

const form = useForm<ReceiptForm>({
    receipts: (props.purchaseOrder.lines ?? []).map((line) => ({
        line_id: line.id!,
        receive_qty: Math.max(0, Number(line.ordered_qty) - Number(line.received_qty ?? 0)),
    })),
});

const remaining = (line: NonNullable<PurchaseOrderRecord['lines']>[number]) =>
    Math.max(0, Number(line.ordered_qty) - Number(line.received_qty ?? 0));

const hasReceivable = computed(() => (props.purchaseOrder.lines ?? []).some((line) => remaining(line) > 0));

const receiveTitle = computed(() => `${t('common.receive')} ${props.purchaseOrder.po_number}`);

async function submitForm() {
    const confirmed = await confirmSave(t('common.receive'), t('entities.order'));
    if (!confirmed) return;
    form.post(route('admin.purchase-orders.receive.store', props.purchaseOrder.id));
}
</script>

<template>
    <Head :title="receiveTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ purchaseOrder.po_number }}</template>

        <FormShell
            :title="receiveTitle"
            :subtitle="`${purchaseOrder.supplier?.name} → ${purchaseOrder.store?.store_name}`"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.purchase-orders.edit', purchaseOrder.id)"
        >
            <template #meta>
                <Badge variant="warning">{{ purchaseOrder.status.replace(/_/g, ' ') }}</Badge>
            </template>

            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="receiveQuantities" description-key="receiveQuantities">
                        <div class="space-y-3">
                            <div
                                v-for="(line, index) in purchaseOrder.lines"
                                :key="line.id"
                                class="grid gap-2 rounded-lg border border-border p-3 md:grid-cols-12"
                            >
                                <div class="md:col-span-5">
                                    <p class="font-medium">{{ line.product?.name }}</p>
                                    <p class="text-xs text-ink-muted">{{ line.product?.sku }}</p>
                                </div>
                                <div class="md:col-span-3 text-sm">
                                    <span class="text-ink-muted">{{ field('remaining') }}:</span> {{ remaining(line) }}
                                </div>
                                <FormField label-key="receiveQty" class="md:col-span-4" :error="form.errors[`receipts.${index}.receive_qty`]">
                                    <Input
                                        v-model="form.receipts[index].receive_qty"
                                        type="number"
                                        min="0"
                                        :max="remaining(line)"
                                        step="any"
                                        :disabled="remaining(line) <= 0"
                                    />
                                </FormField>
                            </div>
                        </div>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.purchase-orders.edit', purchaseOrder.id)"
                    :submit-label="submit('receiveGoods')"
                    :processing="form.processing"
                    :disabled="!hasReceivable"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
