<script setup lang="ts">
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    setting: {
        slug: string;
        storefront_name: string;
        primary_color: string;
        accent_color: string;
        logo_url?: string | null;
        support_phone: string | null;
        support_email: string | null;
    };
    store?: {
        store_name?: string;
        logo_url?: string | null;
    };
    customer?: { id: string; name: string } | null;
    isOwner?: boolean;
    order: {
        uuid: string;
        order_number: string;
        status: string;
        fulfillment_type: string;
        payment_method: string;
        payment_status: string;
        guest_name: string;
        guest_phone: string;
        guest_email: string | null;
        subtotal: string | number;
        tax_total: string | number;
        discount_total: string | number;
        delivery_fee: string | number;
        grand_total: string | number;
        currency: string;
        customer_notes: string | null;
        rejection_reason?: string | null;
        created_at: string | null;
        accepted_at?: string | null;
        ready_at?: string | null;
        completed_at?: string | null;
        lines: Array<{
            name: string;
            qty: string | number;
            unit_price: string | number;
            line_total: string | number;
            modifiers: Array<{ modifier_group_name: string; option_name: string }>;
        }>;
    };
}>();

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    accepted: 'Accepted',
    preparing: 'Preparing',
    ready: 'Ready for pickup',
    completed: 'Completed',
    cancelled: 'Cancelled',
    rejected: 'Rejected',
};

const statusSteps = ['pending', 'accepted', 'preparing', 'ready', 'completed'] as const;

const activeStepIndex = computed(() => {
    const status = props.order.status;
    if (status === 'rejected' || status === 'cancelled') return -1;
    const idx = statusSteps.indexOf(status as (typeof statusSteps)[number]);
    return idx >= 0 ? idx : 0;
});

function money(amount: string | number): string {
    return `${props.order.currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function statusLabel(status: string): string {
    return statusLabels[status] || status;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />
    <StorefrontLayout
        :store-name="setting.storefront_name"
        :primary-color="setting.primary_color"
        :accent-color="setting.accent_color"
        :logo-url="store?.logo_url || setting.logo_url"
    >
        <section class="sf-confirm">
            <p class="sf-eyebrow">Order {{ order.status === 'pending' ? 'received' : 'update' }}</p>
            <h1>{{ order.order_number }}</h1>
            <p class="sf-status">
                Status:
                <strong class="sf-status__badge" :data-status="order.status">{{ statusLabel(order.status) }}</strong>
            </p>
            <p class="sf-copy">
                Thanks, {{ order.guest_name }}. We’ll prepare your
                {{ order.fulfillment_type.replace('_', ' ') }} order shortly.
            </p>

            <div v-if="activeStepIndex >= 0" class="sf-steps" aria-label="Order progress">
                <div
                    v-for="(step, index) in statusSteps"
                    :key="step"
                    class="sf-steps__item"
                    :class="{ done: index <= activeStepIndex, current: index === activeStepIndex }"
                >
                    <span>{{ statusLabel(step) }}</span>
                </div>
            </div>
            <p v-if="order.rejection_reason" class="sf-warn">Reason: {{ order.rejection_reason }}</p>

            <div class="sf-panel">
                <h2>Summary</h2>
                <ul>
                    <li v-for="(line, idx) in order.lines" :key="idx" class="sf-line">
                        <div>
                            <p>{{ Number(line.qty) }} × {{ line.name }}</p>
                            <p
                                v-for="(mod, midx) in line.modifiers"
                                :key="midx"
                                class="sf-mod"
                            >
                                {{ mod.modifier_group_name }}: {{ mod.option_name }}
                            </p>
                        </div>
                        <strong>{{ money(line.line_total) }}</strong>
                    </li>
                </ul>
                <div class="sf-totals">
                    <div><span>Subtotal</span><span>{{ money(order.subtotal) }}</span></div>
                    <div><span>Tax</span><span>{{ money(order.tax_total) }}</span></div>
                    <div><span>Delivery</span><span>{{ money(order.delivery_fee) }}</span></div>
                    <div class="sf-grand"><span>Total</span><strong>{{ money(order.grand_total) }}</strong></div>
                </div>
                <p class="sf-meta">Payment: {{ order.payment_method }} ({{ order.payment_status }})</p>
                <p v-if="setting.support_phone || setting.support_email" class="sf-meta">
                    Questions?
                    <template v-if="setting.support_phone"> Call {{ setting.support_phone }}</template>
                    <template v-if="setting.support_email"> · {{ setting.support_email }}</template>
                </p>
            </div>

            <div class="sf-actions">
                <Link :href="route('storefront.show', setting.slug)" class="sf-btn">Back to store</Link>
                <Link
                    v-if="customer || isOwner"
                    :href="route('storefront.orders.index', setting.slug)"
                    class="sf-btn sf-btn--ghost"
                >
                    View my orders
                </Link>
            </div>
        </section>
    </StorefrontLayout>
</template>

<style scoped>
.sf-confirm {
    max-width: 40rem;
    margin: 0 auto;
    padding: 1rem 0 2rem;
}
.sf-eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.75rem;
    color: var(--sf-muted, #5b716e);
}
.sf-confirm h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    color: var(--sf-primary);
    letter-spacing: -0.03em;
    margin: 0.35rem 0;
}
.sf-status {
    margin-bottom: 0.75rem;
}
.sf-status__badge {
    display: inline-flex;
    margin-left: 0.25rem;
    border-radius: 999px;
    padding: 0.2rem 0.55rem;
    font-size: 0.78rem;
    background: #f1f5f9;
}
.sf-status__badge[data-status='pending'] { background: #fff7ed; color: #c2410c; }
.sf-status__badge[data-status='accepted'],
.sf-status__badge[data-status='preparing'] { background: #eff6ff; color: #1d4ed8; }
.sf-status__badge[data-status='ready'] { background: #ecfdf5; color: #047857; }
.sf-status__badge[data-status='completed'] { background: #f0fdf4; color: #166534; }
.sf-status__badge[data-status='cancelled'],
.sf-status__badge[data-status='rejected'] { background: #fef2f2; color: #b91c1c; }
.sf-copy {
    color: #475569;
    margin-bottom: 1rem;
}
.sf-warn {
    color: #b91c1c;
    font-weight: 600;
    margin-bottom: 1rem;
}
.sf-steps {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.35rem;
    margin-bottom: 1.25rem;
}
.sf-steps__item {
    border-radius: 0.55rem;
    background: #f1f5f9;
    color: #94a3b8;
    padding: 0.45rem 0.35rem;
    text-align: center;
    font-size: 0.68rem;
    font-weight: 700;
}
.sf-steps__item.done { background: color-mix(in srgb, var(--sf-primary) 16%, white); color: var(--sf-primary); }
.sf-steps__item.current { background: var(--sf-primary); color: #fff; }
.sf-panel {
    background: white;
    border-radius: 1rem;
    padding: 1rem;
    border: 1px solid color-mix(in srgb, var(--sf-primary) 12%, transparent);
    margin-bottom: 1rem;
}
.sf-panel h2 {
    color: var(--sf-primary);
    margin-bottom: 0.75rem;
}
.sf-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.55rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.sf-mod {
    font-size: 0.75rem;
    color: #64748b;
}
.sf-totals {
    margin-top: 0.75rem;
    display: grid;
    gap: 0.35rem;
}
.sf-totals > div {
    display: flex;
    justify-content: space-between;
}
.sf-grand {
    padding-top: 0.5rem;
    border-top: 1px solid #e2e8f0;
    font-size: 1.05rem;
}
.sf-meta {
    margin-top: 0.75rem;
    font-size: 0.85rem;
    color: #64748b;
}
.sf-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}
.sf-btn {
    display: inline-flex;
    border-radius: 0.7rem;
    background: var(--sf-primary);
    color: white;
    padding: 0.7rem 1.1rem;
    font-weight: 650;
}
.sf-btn--ghost {
    background: transparent;
    color: var(--sf-primary);
    border: 1px solid color-mix(in srgb, var(--sf-primary) 28%, transparent);
}
@media (max-width: 640px) {
    .sf-steps { grid-template-columns: 1fr 1fr; }
}
</style>
