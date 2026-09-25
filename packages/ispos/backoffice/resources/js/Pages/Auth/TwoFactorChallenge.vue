<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import InputError from '@/Components/InputError.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const { t } = useLocale();

const form = useForm({
    code: '',
    remember_device: false,
});

function submit() {
    form.post(route('two-factor.login.store'), {
        onFinish: () => form.reset('code'),
    });
}
</script>

<template>
    <AuthLayout
        :welcome-title="t('auth.twoFactorTitle')"
        :welcome-subtitle="t('auth.twoFactorSubtitle')"
    >
        <Head :title="t('auth.twoFactorTitle')" />

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label class="ui-label" for="code">{{ t('auth.twoFactorCode') }}</label>
                <Input
                    id="code"
                    v-model="form.code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                    :placeholder="t('auth.twoFactorCodePlaceholder')"
                />
                <InputError class="mt-1.5" :message="form.errors.code" />
            </div>

            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-line/80 bg-surface-muted/35 px-3.5 py-3">
                <input
                    v-model="form.remember_device"
                    type="checkbox"
                    class="ui-focus mt-0.5 rounded border-line text-accent"
                />
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink">{{ t('auth.twoFactorRememberDevice') }}</span>
                    <span class="mt-0.5 block text-xs leading-relaxed text-ink-muted">
                        {{ t('auth.twoFactorRememberDeviceHint') }}
                    </span>
                </span>
            </label>

            <p class="text-xs leading-relaxed text-ink-muted">{{ t('auth.twoFactorRecoveryHint') }}</p>

            <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                {{ form.processing ? t('auth.twoFactorVerifying') : t('auth.twoFactorContinue') }}
            </Button>
        </form>
    </AuthLayout>
</template>
