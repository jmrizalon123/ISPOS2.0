<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import FormField from '@/Components/forms/FormField.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useForm } from '@inertiajs/vue3';

const { t } = useLocale();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function focusField(id: string) {
    document.getElementById(id)?.focus();
}

async function updatePassword() {
    if (!(await confirmSave(t('common.save'), t('profile.password')))) {
        return;
    }

    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                focusField('password');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                focusField('current_password');
            }
        },
    });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="updatePassword">
        <FormField
            label-key="currentPassword"
            required
            html-for="current_password"
            :error="form.errors.current_password"
            class="sm:max-w-sm"
        >
            <Input
                id="current_password"
                v-model="form.current_password"
                type="password"
                autocomplete="current-password"
            />
        </FormField>

        <div class="form-grid">
            <FormField label-key="newPassword" required html-for="password" :error="form.errors.password">
                <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
            </FormField>

            <FormField
                label-key="confirmPassword"
                required
                html-for="password_confirmation"
                :error="form.errors.password_confirmation"
            >
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </FormField>
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
