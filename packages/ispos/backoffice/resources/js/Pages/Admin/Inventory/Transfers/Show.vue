<script setup lang="ts">
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import { useLineTableColumn } from '@/Composables/useTableColumns';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Location {
    id: string;
    store_name: string;
    store_code: string;
}

interface TransferDetail {
    id: string;
    transfer_no: string;
    transfer_number: string;
    transfer_type: string;
    priority: string;
    status: string;
    reason: string | null;
    reference_no: string | null;
    notes: string | null;
    transfer_date: string | null;
    requested_date: string | null;
    expected_date: string | null;
    transferred_at: string | null;
    received_at: string | null;
    total_items: number;
    total_quantity: string;
    from_store: Location | null;
    to_store: Location | null;
    from_warehouse: Location | null;
    to_warehouse: Location | null;
    requested_by: string | null;
    received_by: string | null;
    lines: Array<{
        id: string;
        line_number: number;
        sku: string | null;
        barcode: string | null;
        product_name: string | null;
        requested_quantity: string;
        received_quantity: string;
        quantity: string;
        status: string;
        product: { id: string | null; sku: string | null; name: string | null };
    }>;
}

const page = useModulePage('transfers');
const { t, field, card } = useLocale();
const lineCol = useLineTableColumn();

const props = defineProps<{
    transfer: TransferDetail;
}>();

const columns = computed(() => [
    lineCol.value,
    { key: 'sku', label: t('columns.sku') },
    { key: 'name', label: t('columns.product') },
    { key: 'requested_quantity', label: t('columns.requested') },
    { key: 'received_quantity', label: t('columns.received') },
    { key: 'status', label: t('common.status') },
]);

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function formatDay(value: string | null): string {
    return value ?? '—';
}

function locationLabel(location: Location | null): string {
    return location ? `${location.store_name} (${location.store_code})` : '—';
}

function statusVariant(status: string): 'neutral' | 'success' | 'warning' | 'danger' | 'accent' {
    if (status === 'received') return 'success';
    if (status === 'cancelled' || status === 'rejected') return 'danger';
    if (status === 'in_transit' || status === 'processing' || status === 'partially_received') return 'warning';
    if (status === 'approved') return 'accent';
    return 'neutral';
}
</script>

<template>
    <Head :title="`${t('entities.transfer')} ${transfer.transfer_no || transfer.transfer_number}`" />
    <AppLayout>
        <template #header>{{ transfer.transfer_no || transfer.transfer_number }}</template>
        <template #subheader>{{ t('subheaders.stockTransfer') }}</template>

        <div class="index-page flex flex-col gap-2">
            <IndexPageHeader
                :back-href="route('admin.inventory.transfers.index')"
                :eyebrow="page.eyebrow"
                :title="transfer.transfer_no || transfer.transfer_number"
                :description="`${locationLabel(transfer.from_warehouse || transfer.from_store)} → ${locationLabel(transfer.to_warehouse || transfer.to_store)}`"
            >
                <template #meta>
                    <Badge :variant="statusVariant(transfer.status)">{{ transfer.status.replaceAll('_', ' ') }}</Badge>
                    <Badge variant="neutral">{{ transfer.transfer_type.replaceAll('_', ' ') }}</Badge>
                    <Badge variant="neutral">{{ transfer.priority }}</Badge>
                </template>
            </IndexPageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card :title="card('details')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('fromStore') }}</dt>
                            <dd>{{ locationLabel(transfer.from_store) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('toStore') }}</dt>
                            <dd>{{ locationLabel(transfer.to_store) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('fromWarehouse') }}</dt>
                            <dd>{{ locationLabel(transfer.from_warehouse) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('toWarehouse') }}</dt>
                            <dd>{{ locationLabel(transfer.to_warehouse) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('transferDate') }}</dt>
                            <dd>{{ formatDay(transfer.transfer_date) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('requestedDate') }}</dt>
                            <dd>{{ formatDay(transfer.requested_date) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('expectedDate') }}</dt>
                            <dd>{{ formatDay(transfer.expected_date) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('received') }}</dt>
                            <dd>{{ formatDate(transfer.received_at || transfer.transferred_at) }}</dd>
                        </div>
                    </dl>
                </Card>
                <Card :title="card('reference')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('referenceNo') }}</dt>
                            <dd>{{ transfer.reference_no || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('reason') }}</dt>
                            <dd class="text-right">{{ transfer.reason || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('requestedBy') }}</dt>
                            <dd>{{ transfer.requested_by || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('receivedBy') }}</dt>
                            <dd>{{ transfer.received_by || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('totalItems') }}</dt>
                            <dd>{{ transfer.total_items }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('totalQty') }}</dt>
                            <dd class="font-mono">{{ transfer.total_quantity }}</dd>
                        </div>
                        <div v-if="transfer.notes" class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ field('notes') }}</dt>
                            <dd class="text-right">{{ transfer.notes }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>

            <Card :title="card('items')">
                <DataTable :columns="columns" :rows="transfer.lines" viewport-fit compact>
                    <template #cell-line_number="{ row }">
                        <span class="tabular-nums text-ink-muted">{{ row.line_number }}</span>
                    </template>
                    <template #cell-sku="{ row }">
                        {{ row.sku || row.product.sku }}
                    </template>
                    <template #cell-name="{ row }">
                        {{ row.product_name || row.product.name }}
                    </template>
                    <template #cell-requested_quantity="{ row }">
                        <span class="font-mono tabular-nums">{{ row.requested_quantity }}</span>
                    </template>
                    <template #cell-received_quantity="{ row }">
                        <span class="font-mono tabular-nums">{{ row.received_quantity }}</span>
                    </template>
                    <template #cell-status="{ row }">
                        <Badge :variant="statusVariant(row.status)">{{ row.status.replaceAll('_', ' ') }}</Badge>
                    </template>
                </DataTable>
            </Card>
        </div>
    </AppLayout>
</template>
