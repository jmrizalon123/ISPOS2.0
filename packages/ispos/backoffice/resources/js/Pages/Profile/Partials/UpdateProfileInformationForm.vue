<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import FormField from '@/Components/forms/FormField.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const { t } = useLocale();

const user = usePage().props.auth.user!;

const form = useForm({
    name: user.name,
    email: user.email,
});

async function submitProfile() {
    if (!(await confirmSave(t('common.save'), t('profile.title')))) {
        return;
    }

    form.patch(route('profile.update'), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submitProfile">
        <div class="form-grid">
            <FormField label-key="name" required html-for="name" :error="form.errors.name">
                <Input id="name" v-model="form.name" required autofocus autocomplete="name" />
            </FormField>

            <FormField label-key="email" required html-for="email" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" required autocomplete="username" />
            </FormField>
        </div>

        <div
            v-if="mustVerifyEmail && user.email_verified_at === null"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
        >
            <p>{{ t('profile.emailUnverified') }}</p>

            <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                class="mt-1 font-semibold underline underline-offset-2 hover:no-underline ui-focus"
            >
                {{ t('profile.resendVerification') }}
            </Link>

            <p
                v-show="status === 'verification-link-sent'"
                class="mt-2 font-medium text-emerald-700 dark:text-emerald-300"
            >
                {{ t('profile.verificationSent') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {{ t('profile.saved') }}
                </p>
            </Transition>
        </div>
    </form>
</template>
