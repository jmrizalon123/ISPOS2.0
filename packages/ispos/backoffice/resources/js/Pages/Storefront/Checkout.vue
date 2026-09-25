<script setup lang="ts">
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface CartItem {
    key: string;
    product_id: string;
    product_variant_id: string | null;
    name: string;
    qty: number;
    unit_price: number;
    modifiers: Array<{ product_modifier_option_id: string; option_name: string; price_adjustment: number }>;
}

const props = defineProps<{
    setting: {
        slug: string;
        storefront_name: string;
        primary_color: string;
        accent_color: string;
        logo_url?: string | null;
        accept_pickup: boolean;
        accept_delivery: boolean;
        accept_dine_in: boolean;
        min_order_amount: string | number;
        delivery_fee: string | number;
        free_delivery_threshold: string | number | null;
        payment_methods: string[];
        preparation_minutes: number;
    };
    store: {
        store_name: string;
        currency: string;
        store_category: string | null;
        logo_url?: string | null;
    };
    customer: {
        id: string;
        name: string;
        email: string | null;
        phone: string | null;
        address_line_1: string | null;
        city: string | null;
        province: string | null;
        postal_code: string | null;
    };
    acceptingOrders: boolean;
}>();

const cart = ref<CartItem[]>([]);
const storageKey = `ispos-storefront-cart:${props.setting.slug}`;

onMounted(() => {
    try {
        const raw = localStorage.getItem(storageKey);
        if (raw) cart.value = JSON.parse(raw);
    } catch {
        cart.value = [];
    }
});

const defaultFulfillment = computed(() => {
    if (props.setting.accept_pickup) return 'pickup';
    if (props.setting.accept_delivery) return 'delivery';
    if (props.setting.accept_dine_in) return 'dine_in';
    return 'pickup';
});

interface CheckoutLine {
    product_id: string;
    product_variant_id: string | null;
    qty: number;
    modifiers: Array<{ product_modifier_option_id: string; option_name: string; price_adjustment: number }>;
}

interface CheckoutForm {
    guest_name: string;
    guest_email: string;
    guest_phone: string;
    fulfillment_type: string;
    payment_method: string;
    delivery_address_line_1: string;
    delivery_address_line_2: string;
    delivery_barangay: string;
    delivery_city: string;
    delivery_province: string;
    delivery_postal_code: string;
    delivery_notes: string;
    customer_notes: string;
    lines: CheckoutLine[];
}

const form = useForm<CheckoutForm>({
    guest_name: props.customer.name || '',
    guest_email: props.customer.email || '',
    guest_phone: props.customer.phone || '',
    fulfillment_type: defaultFulfillment.value,
    payment_method: props.setting.payment_methods[0] ?? 'cod',
    delivery_address_line_1: props.customer.address_line_1 || '',
    delivery_address_line_2: '',
    delivery_barangay: '',
    delivery_city: props.customer.city || '',
    delivery_province: props.customer.province || '',
    delivery_postal_code: props.customer.postal_code || '',
    delivery_notes: '',
    customer_notes: '',
    lines: [],
});

function logout() {
    router.post(route('storefront.logout', props.setting.slug));
}

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.qty * item.unit_price, 0));

const deliveryFee = computed(() => {
    if (form.fulfillment_type !== 'delivery') return 0;
    const threshold = Number(props.setting.free_delivery_threshold ?? 0);
    if (threshold > 0 && subtotal.value >= threshold) return 0;
    return Number(props.setting.delivery_fee ?? 0);
});

const estimatedTotal = computed(() => subtotal.value + deliveryFee.value);

const errorList = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;
    return Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([key, message]) => ({ key, message: String(message) }));
});

const bagError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;
    return errors.lines || errors.cart || errors.store || '';
});

const fieldErrorMap: Record<string, string> = {
    guest_name: 'Name',
    guest_phone: 'Phone',
    guest_email: 'Email',
    fulfillment_type: 'Fulfillment',
    payment_method: 'Payment',
    delivery_address_line_1: 'Address',
    delivery_city: 'City',
    lines: 'Cart',
    cart: 'Cart',
    store: 'Store',
};

function fieldLabel(key: string): string {
    if (fieldErrorMap[key]) return fieldErrorMap[key];
    if (key.startsWith('lines.')) return 'Cart item';
    return key.replace(/_/g, ' ');
}

function money(amount: number | string): string {
    return `${props.store.currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function paymentLabel(method: string): string {
    const labels: Record<string, string> = {
        cod: 'Cash on delivery',
        pay_at_store: 'Pay at store',
        gcash: 'GCash',
        card: 'Card',
        bank_transfer: 'Bank transfer',
    };
    return labels[method] ?? method;
}

function hasError(field: string): boolean {
    return Boolean((form.errors as Record<string, string | undefined>)[field]);
}

function placeOrder() {
    if (!cart.value.length) {
        return;
    }

    form.lines = cart.value.map((item) => ({
        product_id: String(item.product_id),
        product_variant_id: item.product_variant_id ? String(item.product_variant_id) : null,
        qty: Number(item.qty),
        modifiers: Array.isArray(item.modifiers)
            ? item.modifiers.map((mod) => ({
                  product_modifier_option_id: String(mod.product_modifier_option_id),
                  option_name: String(mod.option_name ?? ''),
                  price_adjustment: Number(mod.price_adjustment ?? 0),
              }))
            : [],
    }));

    form.transform((data) => ({
        ...data,
        guest_email: data.guest_email?.trim() ? data.guest_email.trim() : null,
        delivery_address_line_1: data.delivery_address_line_1 || null,
        delivery_address_line_2: data.delivery_address_line_2 || null,
        delivery_barangay: data.delivery_barangay || null,
        delivery_city: data.delivery_city || null,
        delivery_province: data.delivery_province || null,
        delivery_postal_code: data.delivery_postal_code || null,
        delivery_notes: data.delivery_notes || null,
        customer_notes: data.customer_notes || null,
    })).post(route('storefront.checkout.store', props.setting.slug), {
        preserveScroll: true,
        onSuccess: () => {
            localStorage.removeItem(storageKey);
        },
    });
}
</script>

<template>
    <Head :title="`Checkout — ${setting.storefront_name}`" />
    <StorefrontLayout
        :store-name="setting.storefront_name"
        :primary-color="setting.primary_color"
        :accent-color="setting.accent_color"
        :logo-url="store.logo_url || setting.logo_url"
    >
        <div class="sf-checkout">
            <div class="sf-checkout-head">
                <Link :href="route('storefront.show', setting.slug)" class="sf-back">← Back to store</Link>
                <h1>Checkout</h1>
                <p class="sf-account">
                    Signed in as <strong>{{ customer.name }}</strong>
                    <button type="button" class="sf-logout" @click="logout">Sign out</button>
                </p>
                <p v-if="!acceptingOrders" class="sf-warn">This store is closed for online orders right now.</p>
                <p v-else-if="!cart.length" class="sf-warn">Your cart is empty.</p>
            </div>

            <div v-if="errorList.length" class="sf-alert" role="alert">
                <div>
                    <strong>Please fix the following:</strong>
                    <ul>
                        <li v-for="item in errorList" :key="item.key">
                            <span>{{ fieldLabel(item.key) }}:</span> {{ item.message }}
                        </li>
                    </ul>
                </div>
            </div>

            <form class="sf-checkout-grid" @submit.prevent="placeOrder">
                <section class="sf-panel">
                    <h2>Your details</h2>
                    <label :class="{ 'sf-invalid': hasError('guest_name') }">
                        Name *
                        <input v-model="form.guest_name" required autocomplete="name" />
                        <span v-if="form.errors.guest_name" class="sf-error">{{ form.errors.guest_name }}</span>
                    </label>
                    <label :class="{ 'sf-invalid': hasError('guest_phone') }">
                        Phone *
                        <input v-model="form.guest_phone" required autocomplete="tel" />
                        <span v-if="form.errors.guest_phone" class="sf-error">{{ form.errors.guest_phone }}</span>
                    </label>
                    <label :class="{ 'sf-invalid': hasError('guest_email') }">
                        Email
                        <input v-model="form.guest_email" type="email" autocomplete="email" />
                        <span v-if="form.errors.guest_email" class="sf-error">{{ form.errors.guest_email }}</span>
                    </label>

                    <h2>Fulfillment</h2>
                    <div class="sf-options" :class="{ 'sf-invalid': hasError('fulfillment_type') }">
                        <label v-if="setting.accept_pickup"><input v-model="form.fulfillment_type" type="radio" value="pickup" /> Pickup</label>
                        <label v-if="setting.accept_delivery"><input v-model="form.fulfillment_type" type="radio" value="delivery" /> Delivery</label>
                        <label v-if="setting.accept_dine_in"><input v-model="form.fulfillment_type" type="radio" value="dine_in" /> Dine-in</label>
                    </div>
                    <span v-if="form.errors.fulfillment_type" class="sf-error">{{ form.errors.fulfillment_type }}</span>

                    <template v-if="form.fulfillment_type === 'delivery'">
                        <label :class="{ 'sf-invalid': hasError('delivery_address_line_1') }">
                            Address *
                            <input v-model="form.delivery_address_line_1" required />
                            <span v-if="form.errors.delivery_address_line_1" class="sf-error">{{ form.errors.delivery_address_line_1 }}</span>
                        </label>
                        <label>
                            Address line 2
                            <input v-model="form.delivery_address_line_2" />
                        </label>
                        <label>
                            Barangay
                            <input v-model="form.delivery_barangay" />
                        </label>
                        <label :class="{ 'sf-invalid': hasError('delivery_city') }">
                            City *
                            <input v-model="form.delivery_city" required />
                            <span v-if="form.errors.delivery_city" class="sf-error">{{ form.errors.delivery_city }}</span>
                        </label>
                        <label>
                            Province
                            <input v-model="form.delivery_province" />
                        </label>
                        <label>
                            Postal code
                            <input v-model="form.delivery_postal_code" />
                        </label>
                        <label>
                            Delivery notes
                            <textarea v-model="form.delivery_notes" rows="2" />
                        </label>
                    </template>

                    <h2>Payment</h2>
                    <div class="sf-options" :class="{ 'sf-invalid': hasError('payment_method') }">
                        <label v-for="method in setting.payment_methods" :key="method">
                            <input v-model="form.payment_method" type="radio" :value="method" />
                            {{ paymentLabel(method) }}
                        </label>
                    </div>
                    <span v-if="form.errors.payment_method" class="sf-error">{{ form.errors.payment_method }}</span>

                    <label>
                        Order notes
                        <textarea v-model="form.customer_notes" rows="2" />
                    </label>
                </section>

                <aside class="sf-panel sf-summary">
                    <h2>Order summary</h2>
                    <ul>
                        <li v-for="item in cart" :key="item.key" class="sf-line">
                            <span>{{ item.qty }} × {{ item.name }}</span>
                            <strong>{{ money(item.qty * item.unit_price) }}</strong>
                        </li>
                    </ul>
                    <div class="sf-totals">
                        <div><span>Subtotal</span><span>{{ money(subtotal) }}</span></div>
                        <div v-if="form.fulfillment_type === 'delivery'"><span>Delivery</span><span>{{ money(deliveryFee) }}</span></div>
                        <div class="sf-grand"><span>Estimated total</span><strong>{{ money(estimatedTotal) }}</strong></div>
                        <p class="sf-note">Tax may be applied at confirmation. Min order: {{ money(setting.min_order_amount) }}.</p>
                    </div>
                    <p
                        v-if="bagError"
                        class="sf-error sf-error--block"
                    >
                        {{ bagError }}
                    </p>
                    <button
                        type="submit"
                        class="sf-submit"
                        :disabled="form.processing || !cart.length || !acceptingOrders"
                    >
                        {{ form.processing ? 'Placing order…' : 'Place order' }}
                    </button>
                </aside>
            </form>
        </div>
    </StorefrontLayout>
</template>

<style scoped>
.sf-checkout-head {
    margin-bottom: 1rem;
}
.sf-checkout-head h1 {
    font-family: Fraunces, Georgia, serif;
    font-size: clamp(1.75rem, 4vw, 2.4rem);
    color: var(--sf-primary);
    letter-spacing: -0.02em;
}
.sf-back {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--sf-primary);
    text-decoration: underline;
    font-size: 0.9rem;
}
.sf-warn {
    margin-top: 0.5rem;
    color: #b45309;
    font-weight: 600;
}
.sf-account {
    margin: 0.35rem 0 0;
    color: #475569;
    font-size: 0.9rem;
}
.sf-logout {
    margin-left: 0.65rem;
    color: var(--sf-primary);
    font-weight: 700;
    text-decoration: underline;
    font-size: 0.85rem;
}
.sf-alert {
    margin-bottom: 1rem;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    border-radius: 0.9rem;
    padding: 0.85rem 1rem;
}
.sf-alert strong {
    display: block;
    margin-bottom: 0.35rem;
}
.sf-alert ul {
    margin: 0;
    padding-left: 1.1rem;
    display: grid;
    gap: 0.25rem;
    font-size: 0.88rem;
}
.sf-alert span {
    font-weight: 700;
}
.sf-checkout-grid {
    display: grid;
    gap: 1rem;
}
@media (min-width: 900px) {
    .sf-checkout-grid {
        grid-template-columns: 1.4fr 0.9fr;
        align-items: start;
    }
}
.sf-panel {
    background: white;
    border-radius: 1rem;
    padding: 1rem;
    border: 1px solid color-mix(in srgb, var(--sf-primary) 12%, transparent);
}
.sf-panel h2 {
    font-family: Fraunces, Georgia, serif;
    font-size: 1.15rem;
    margin: 0.75rem 0;
    color: var(--sf-primary);
}
.sf-panel label {
    display: block;
    margin-bottom: 0.75rem;
    font-size: 0.85rem;
    color: #475569;
}
.sf-panel input:not([type='radio']),
.sf-panel textarea {
    display: block;
    width: 100%;
    margin-top: 0.35rem;
    border: 1px solid #dbe3e8;
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
}
.sf-invalid input:not([type='radio']),
.sf-invalid textarea {
    border-color: #f87171;
    background: #fff7f7;
}
.sf-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.sf-options label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0;
}
.sf-line {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.45rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
}
.sf-totals {
    margin-top: 0.75rem;
    display: grid;
    gap: 0.35rem;
    font-size: 0.9rem;
}
.sf-totals > div {
    display: flex;
    justify-content: space-between;
}
.sf-grand {
    margin-top: 0.35rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e2e8f0;
    font-size: 1rem;
}
.sf-note {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}
.sf-submit {
    margin-top: 1rem;
    width: 100%;
    border-radius: 0.7rem;
    background: var(--sf-primary);
    color: white;
    padding: 0.75rem;
    font-weight: 700;
}
.sf-submit:disabled {
    opacity: 0.5;
}
.sf-error {
    display: block;
    margin-top: 0.25rem;
    color: #b91c1c;
    font-size: 0.8rem;
}
.sf-error--block {
    margin: 0.75rem 0 0;
    padding: 0.65rem 0.75rem;
    border-radius: 0.65rem;
    background: #fef2f2;
}
</style>
