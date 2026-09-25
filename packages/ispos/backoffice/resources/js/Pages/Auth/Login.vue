<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import InputError from '@/Components/InputError.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const { t, field, placeholder } = useLocale();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout>
        <Head :title="t('auth.login')" />

        <div
            v-if="status"
            class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label class="ui-label" for="email">{{ field('emailAddress') }}</label>
                <Input id="email" v-model="form.email" type="email" :placeholder="placeholder('email')" autofocus autocomplete="username" />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label class="text-sm font-medium text-ink" for="password">{{ field('password') }}</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-accent hover:text-accent-hover"
                    >
                        {{ t('auth.forgotPassword') }}
                    </Link>
                </div>
                <Input id="password" v-model="form.password" type="password" :placeholder="placeholder('password')" autocomplete="current-password" />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-muted">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30"
                />
                {{ t('auth.keepSignedIn') }}
            </label>

            <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                {{ form.processing ? t('auth.signingIn') : t('auth.signIn') }}
            </Button>
        </form>
    </AuthLayout>
</template>
