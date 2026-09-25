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
        support_phone?: string | null;
        support_email?: string | null;
    };
    store: {
        store_name: string;
        currency: string;
        logo_url?: string | null;
    };
    customer: { id: string; name: string };
    orders: Array<{
        uuid: string;
        order_number: string;
        status: string;
        fulfillment_type: string;
        payment_method: string;
        payment_status: string;
        grand_total: string | number;
        currency: string;
        item_count: number;
        created_at: string | null;
    }>;
}>();

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    accepted: 'Accepted',
    preparing: 'Preparing',
    ready: 'Ready',
    completed: 'Completed',
    cancelled: 'Cancelled',
    rejected: 'Rejected',
};

function money(amount: string | number, currency = props.store.currency): string {
    return `${currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function statusLabel(status: string): string {
    return statusLabels[status] || status;
}

function formatDate(value: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

const openCount = computed(
    () => props.orders.filter((o) => !['completed', 'cancelled', 'rejected'].includes(o.status)).length,
);
</script>

<template>
    <Head :title="`My orders · ${setting.storefront_name}`" />
    <StorefrontLayout
        :store-name="setting.storefront_name"
        :primary-color="setting.primary_color"
        :accent-color="setting.accent_color"
        :logo-url="store.logo_url || setting.logo_url"
    >
        <div class="sf-orders">
            <div class="sf-orders__head">
                <div>
                    <Link :href="route('storefront.show', setting.slug)" class="sf-back">← Back to store</Link>
                    <h1>My orders</h1>
                    <p class="sf-orders__sub">
                        {{ customer.name }} · {{ orders.length }} total
                        <template v-if="openCount"> · {{ openCount }} active</template>
                    </p>
                </div>
                <Link :href="route('storefront.show', setting.slug)" class="sf-btn sf-btn--ghost">Continue shopping</Link>
            </div>

            <div v-if="!orders.length" class="sf-orders__empty">
                <strong>No orders yet</strong>
                <p>When you place an order, you can track its status here.</p>
                <Link :href="route('storefront.show', setting.slug)" class="sf-btn">Shop now</Link>
            </div>

            <ul v-else class="sf-orders__list">
                <li v-for="order in orders" :key="order.uuid" class="sf-orders__card">
                    <div class="sf-orders__card-top">
                        <div>
                            <p class="sf-orders__number">{{ order.order_number }}</p>
                            <p class="sf-orders__meta">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <span class="sf-orders__status" :data-status="order.status">
                            {{ statusLabel(order.status) }}
                        </span>
                    </div>
                    <div class="sf-orders__card-body">
                        <p>{{ order.item_count }} item{{ order.item_count === 1 ? '' : 's' }} · {{ order.fulfillment_type.replace('_', ' ') }}</p>
                        <strong>{{ money(order.grand_total, order.currency) }}</strong>
                    </div>
                    <div class="sf-orders__card-foot">
                        <span>Payment: {{ order.payment_method }} ({{ order.payment_status }})</span>
                        <Link :href="route('storefront.orders.show', [setting.slug, order.uuid])" class="sf-orders__link">
                            View status →
                        </Link>
                    </div>
                </li>
            </ul>
        </div>
    </StorefrontLayout>
</template>

<style scoped>
.sf-orders {
    max-width: 44rem;
    margin: 0 auto;
    padding: 1rem 0 2.5rem;
}
.sf-orders__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.sf-back {
    display: inline-block;
    margin-bottom: 0.4rem;
    color: var(--sf-primary);
    text-decoration: underline;
    font-size: 0.88rem;
}
.sf-orders h1 {
    margin: 0;
    font-family: Fraunces, Georgia, serif;
    font-size: clamp(1.75rem, 4vw, 2.4rem);
    color: var(--sf-primary);
    letter-spacing: -0.02em;
}
.sf-orders__sub {
    margin: 0.35rem 0 0;
    color: #64748b;
    font-size: 0.9rem;
}
.sf-orders__empty {
    display: grid;
    place-items: center;
    gap: 0.45rem;
    text-align: center;
    padding: 3rem 1rem;
    border-radius: 1rem;
    background: #fff;
    border: 1px solid color-mix(in srgb, var(--sf-primary) 12%, transparent);
}
.sf-orders__empty strong {
    font-size: 1.1rem;
}
.sf-orders__empty p {
    margin: 0;
    color: #64748b;
}
.sf-orders__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.85rem;
}
.sf-orders__card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid color-mix(in srgb, var(--sf-primary) 12%, transparent);
    padding: 1rem;
}
.sf-orders__card-top {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
}
.sf-orders__number {
    margin: 0;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.sf-orders__meta {
    margin: 0.2rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
}
.sf-orders__status {
    border-radius: 999px;
    padding: 0.3rem 0.65rem;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #f1f5f9;
    color: #475569;
}
.sf-orders__status[data-status='pending'] { background: #fff7ed; color: #c2410c; }
.sf-orders__status[data-status='accepted'],
.sf-orders__status[data-status='preparing'] { background: #eff6ff; color: #1d4ed8; }
.sf-orders__status[data-status='ready'] { background: #ecfdf5; color: #047857; }
.sf-orders__status[data-status='completed'] { background: #f0fdf4; color: #166534; }
.sf-orders__status[data-status='cancelled'],
.sf-orders__status[data-status='rejected'] { background: #fef2f2; color: #b91c1c; }
.sf-orders__card-body {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.85rem;
    font-size: 0.92rem;
}
.sf-orders__card-body p { margin: 0; color: #475569; }
.sf-orders__card-foot {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    margin-top: 0.85rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
    font-size: 0.82rem;
    color: #64748b;
}
.sf-orders__link {
    color: var(--sf-primary);
    font-weight: 700;
}
.sf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: var(--sf-primary);
    color: #fff;
    padding: 0.65rem 1rem;
    font-weight: 700;
    font-size: 0.88rem;
}
.sf-btn--ghost {
    background: transparent;
    color: var(--sf-primary);
    border: 1px solid color-mix(in srgb, var(--sf-primary) 28%, transparent);
}
@media (max-width: 640px) {
    .sf-orders__head { flex-direction: column; }
    .sf-orders__card-foot { flex-direction: column; align-items: flex-start; }
}
</style>
