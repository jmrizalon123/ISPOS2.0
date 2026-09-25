<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import FormField from '@/Components/forms/FormField.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import { confirmAction } from '@/Composables/useConfirm';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const { t } = useLocale();

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

async function confirmUserDeletion() {
    const confirmed = await confirmAction({
        title: t('profile.deleteConfirmTitle'),
        message: t('profile.deleteAccountDesc'),
        confirmLabel: t('common.confirm'),
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    confirmingUserDeletion.value = true;
    nextTick(() => document.getElementById('delete_password')?.focus());
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => document.getElementById('delete_password')?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
}

function closeModal() {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
}
</script>

<template>
    <div>
        <Button variant="danger" @click="confirmUserDeletion">
            {{ t('profile.deleteAccount') }}
        </Button>

        <Modal :show="confirmingUserDeletion" max-width="md" @close="closeModal">
            <div class="p-5 sm:p-6">
                <h2 class="font-display text-base font-bold tracking-tight text-ink">
                    {{ t('profile.deleteConfirmTitle') }}
                </h2>
                <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">
                    {{ t('profile.deleteConfirmMessage') }}
                </p>

                <FormField class="mt-5" label-key="password" required html-for="delete_password" :error="form.errors.password">
                    <Input
                        id="delete_password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        @keyup.enter="deleteUser"
                    />
                </FormField>

                <div class="mt-6 flex justify-end gap-2">
                    <Button variant="secondary" @click="closeModal">{{ t('common.cancel') }}</Button>
                    <Button variant="danger" :disabled="form.processing" @click="deleteUser">
                        {{ t('profile.deleteAccount') }}
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
