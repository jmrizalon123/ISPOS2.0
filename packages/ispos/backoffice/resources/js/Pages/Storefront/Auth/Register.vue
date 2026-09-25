<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    setting: {
        slug: string;
        storefront_name: string;
        primary_color: string;
        accent_color: string;
        logo_url?: string | null;
    };
    store: { store_name: string };
}>();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('storefront.register.store', props.setting.slug));
}
</script>

<template>
    <Head :title="`Create account · ${setting.storefront_name || store.store_name}`" />
    <div class="sf-auth" :style="{ '--sf-accent': setting.primary_color }">
        <div class="sf-auth__card">
            <Link :href="route('storefront.show', setting.slug)" class="sf-auth__brand">
                <img v-if="setting.logo_url" :src="setting.logo_url" alt="" />
                <span>{{ setting.storefront_name || store.store_name }}</span>
            </Link>
            <h1>Create account</h1>
            <p class="sf-auth__sub">Required to place orders, favorite items, and leave ratings.</p>

            <form class="sf-auth__form" @submit.prevent="submit">
                <div class="sf-auth__row">
                    <label>
                        <span>First name</span>
                        <input v-model="form.first_name" type="text" required autocomplete="given-name" />
                        <em v-if="form.errors.first_name">{{ form.errors.first_name }}</em>
                    </label>
                    <label>
                        <span>Last name</span>
                        <input v-model="form.last_name" type="text" autocomplete="family-name" />
                        <em v-if="form.errors.last_name">{{ form.errors.last_name }}</em>
                    </label>
                </div>
                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" required autocomplete="email" />
                    <em v-if="form.errors.email">{{ form.errors.email }}</em>
                </label>
                <label>
                    <span>Phone</span>
                    <input v-model="form.phone" type="tel" required autocomplete="tel" />
                    <em v-if="form.errors.phone">{{ form.errors.phone }}</em>
                </label>
                <label>
                    <span>Password</span>
                    <input v-model="form.password" type="password" required autocomplete="new-password" />
                    <em v-if="form.errors.password">{{ form.errors.password }}</em>
                </label>
                <label>
                    <span>Confirm password</span>
                    <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
                </label>
                <button type="submit" class="sf-auth__btn" :disabled="form.processing">Create account</button>
            </form>

            <p class="sf-auth__foot">
                Already have an account?
                <Link :href="route('storefront.login', setting.slug)">Sign in</Link>
            </p>
        </div>
    </div>
</template>

<style scoped>
.sf-auth {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 1.5rem;
    background: #f6f6f6;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
}
.sf-auth__card {
    width: min(100%, 28rem);
    border-radius: 1.25rem;
    background: #fff;
    padding: 1.75rem;
    box-shadow: 0 18px 50px rgb(0 0 0 / 0.08);
}
.sf-auth__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 1.25rem;
    color: inherit;
    text-decoration: none;
    font-weight: 800;
}
.sf-auth__brand img {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.55rem;
    object-fit: cover;
}
h1 {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}
.sf-auth__sub {
    margin: 0.4rem 0 1.25rem;
    color: #6b7280;
    font-size: 0.9rem;
}
.sf-auth__form {
    display: grid;
    gap: 0.85rem;
}
.sf-auth__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
label {
    display: grid;
    gap: 0.35rem;
    font-size: 0.85rem;
    font-weight: 600;
}
input {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.7rem 0.85rem;
    font: inherit;
}
em {
    color: #dc2626;
    font-style: normal;
    font-size: 0.78rem;
    font-weight: 500;
}
.sf-auth__btn {
    border-radius: 999px;
    background: var(--sf-accent, #ff5a1f);
    color: #fff;
    padding: 0.85rem 1rem;
    font-weight: 800;
}
.sf-auth__btn:disabled { opacity: 0.6; }
.sf-auth__foot {
    margin: 1.15rem 0 0;
    text-align: center;
    color: #6b7280;
    font-size: 0.88rem;
}
.sf-auth__foot a {
    color: var(--sf-accent, #ff5a1f);
    font-weight: 700;
}
@media (max-width: 520px) {
    .sf-auth__row { grid-template-columns: 1fr; }
}
</style>
