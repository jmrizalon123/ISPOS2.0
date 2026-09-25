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
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('storefront.login.store', props.setting.slug));
}
</script>

<template>
    <Head :title="`Sign in · ${setting.storefront_name || store.store_name}`" />
    <div class="sf-auth" :style="{ '--sf-accent': setting.primary_color }">
        <div class="sf-auth__card">
            <Link :href="route('storefront.show', setting.slug)" class="sf-auth__brand">
                <img v-if="setting.logo_url" :src="setting.logo_url" alt="" />
                <span>{{ setting.storefront_name || store.store_name }}</span>
            </Link>
            <h1>Sign in</h1>
            <p class="sf-auth__sub">Sign in to checkout, save favorites, and rate products.</p>

            <form class="sf-auth__form" @submit.prevent="submit">
                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" required autocomplete="email" />
                    <em v-if="form.errors.email">{{ form.errors.email }}</em>
                </label>
                <label>
                    <span>Password</span>
                    <input v-model="form.password" type="password" required autocomplete="current-password" />
                    <em v-if="form.errors.password">{{ form.errors.password }}</em>
                </label>
                <label class="sf-auth__check">
                    <input v-model="form.remember" type="checkbox" />
                    <span>Remember me</span>
                </label>
                <button type="submit" class="sf-auth__btn" :disabled="form.processing">Sign in</button>
            </form>

            <p class="sf-auth__foot">
                New here?
                <Link :href="route('storefront.register', setting.slug)">Create an account</Link>
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
    width: min(100%, 26rem);
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
label {
    display: grid;
    gap: 0.35rem;
    font-size: 0.85rem;
    font-weight: 600;
}
input[type='email'],
input[type='password'],
input[type='text'],
input[type='tel'] {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.7rem 0.85rem;
    font: inherit;
}
.sf-auth__check {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-weight: 500;
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
</style>
