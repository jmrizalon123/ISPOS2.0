<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import Input from '@/Components/ui/Input.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';

interface OrderLine {
    id: string;
    name: string;
    qty: string | number;
    unit_price: string | number;
    line_total: string | number;
    modifiers?: Array<{ modifier_group_name: string; option_name: string }>;
}

interface OrderDetail {
    id: string;
    order_number: string;
    guest_name: string;
    guest_email: string | null;
    guest_phone: string;
    fulfillment_type: string;
    status: string;
    payment_method: string;
    payment_status: string;
    delivery_address_line_1: string | null;
    delivery_city: string | null;
    delivery_province: string | null;
    delivery_notes: string | null;
    customer_notes: string | null;
    rejection_reason: string | null;
    subtotal: string | number;
    tax_total: string | number;
    discount_total: string | number;
    delivery_fee: string | number;
    grand_total: string | number;
    currency: string;
    created_at: string | null;
    store?: { id: string; store_name: string; store_code: string } | null;
    lines: OrderLine[];
}

const page = useModulePage('onlineOrders');
const { can } = usePermissions();
const { t } = useI18n();
const rejectReason = ref('');

const props = defineProps<{
    order: OrderDetail;
}>();

const statusVariant: Record<string, 'neutral' | 'success' | 'warning' | 'danger'> = {
    pending: 'warning',
    accepted: 'neutral',
    preparing: 'neutral',
    ready: 'success',
    completed: 'success',
    cancelled: 'danger',
    rejected: 'danger',
};

const canManage = computed(() => can('online_store.manage_orders'));

function money(amount: string | number): string {
    return `${props.order.currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function postAction(name: string, data: Record<string, string> = {}) {
    router.post(route(name, props.order.id), data, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${page.showHeader} — ${order.order_number}`" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ order.order_number }}</template>

        <div class="flex flex-col gap-4">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="order.order_number" :description="order.store?.store_name ?? ''">
                <template #meta>
                    <Badge :variant="statusVariant[order.status] ?? 'neutral'">{{ order.status }}</Badge>
                </template>
                <template #actions>
                    <Link :href="route('admin.online-orders.index')">
                        <Button variant="secondary">{{ t('common.back') }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <div v-if="canManage && ['pending', 'accepted', 'preparing', 'ready'].includes(order.status)" class="flex flex-wrap gap-2">
                <Button v-if="order.status === 'pending'" @click="postAction('admin.online-orders.accept')">Accept</Button>
                <Button v-if="order.status === 'pending'" variant="danger" @click="postAction('admin.online-orders.reject', { rejection_reason: rejectReason })">
                    Reject
                </Button>
                <Button
                    v-if="['accepted', 'preparing'].includes(order.status)"
                    @click="postAction('admin.online-orders.ready')"
                >
                    Mark ready
                </Button>
                <Button
                    v-if="['accepted', 'preparing', 'ready'].includes(order.status)"
                    @click="postAction('admin.online-orders.complete')"
                >
                    Complete
                </Button>
                <Button
                    v-if="['pending', 'accepted', 'preparing'].includes(order.status)"
                    variant="secondary"
                    @click="postAction('admin.online-orders.cancel')"
                >
                    Cancel
                </Button>
            </div>

            <div v-if="canManage && order.status === 'pending'" class="max-w-md">
                <Input v-model="rejectReason" placeholder="Rejection reason (optional)" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <h3 class="mb-3 font-semibold text-ink">Customer</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Name</dt><dd>{{ order.guest_name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Phone</dt><dd>{{ order.guest_phone }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Email</dt><dd>{{ order.guest_email || '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Fulfillment</dt><dd>{{ order.fulfillment_type.replace('_', ' ') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Payment</dt><dd>{{ order.payment_method }} ({{ order.payment_status }})</dd></div>
                        <div v-if="order.delivery_address_line_1" class="flex justify-between gap-4">
                            <dt class="text-ink-muted">Address</dt>
                            <dd class="text-right">
                                {{ order.delivery_address_line_1 }}
                                <template v-if="order.delivery_city">, {{ order.delivery_city }}</template>
                                <template v-if="order.delivery_province">, {{ order.delivery_province }}</template>
                            </dd>
                        </div>
                        <div v-if="order.customer_notes" class="flex justify-between gap-4">
                            <dt class="text-ink-muted">Notes</dt>
                            <dd class="text-right">{{ order.customer_notes }}</dd>
                        </div>
                        <div v-if="order.rejection_reason" class="flex justify-between gap-4">
                            <dt class="text-ink-muted">Rejection</dt>
                            <dd class="text-right">{{ order.rejection_reason }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <h3 class="mb-3 font-semibold text-ink">Totals</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Subtotal</dt><dd>{{ money(order.subtotal) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Tax</dt><dd>{{ money(order.tax_total) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Delivery</dt><dd>{{ money(order.delivery_fee) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Discount</dt><dd>{{ money(order.discount_total) }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-edge pt-2 font-semibold">
                            <dt>Grand total</dt>
                            <dd>{{ money(order.grand_total) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 text-ink-muted">
                            <dt>Placed</dt>
                            <dd>{{ order.created_at ? new Date(order.created_at).toLocaleString() : '—' }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>

            <Card>
                <h3 class="mb-3 font-semibold text-ink">Line items</h3>
                <ul class="divide-y divide-edge">
                    <li v-for="line in order.lines" :key="line.id" class="flex items-start justify-between gap-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-ink">{{ line.name }}</p>
                            <p class="text-ink-muted">Qty {{ Number(line.qty) }} × {{ money(line.unit_price) }}</p>
                            <ul v-if="line.modifiers?.length" class="mt-1 text-xs text-ink-muted">
                                <li v-for="(mod, idx) in line.modifiers" :key="idx">
                                    {{ mod.modifier_group_name }}: {{ mod.option_name }}
                                </li>
                            </ul>
                        </div>
                        <p class="font-medium tabular-nums">{{ money(line.line_total) }}</p>
                    </li>
                </ul>
            </Card>
        </div>
    </AppLayout>
</template>
