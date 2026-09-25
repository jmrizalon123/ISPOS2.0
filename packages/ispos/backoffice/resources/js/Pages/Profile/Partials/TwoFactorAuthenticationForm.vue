<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import FormField from '@/Components/forms/FormField.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import { confirmAction } from '@/Composables/useConfirm';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    twoFactor: {
        enabled: boolean;
        pending: boolean;
        qrSvg: string | null;
        manualKey: string | null;
        recoveryCodes: string[] | null;
    };
    compact?: boolean;
}>();

const { t } = useLocale();

const confirmForm = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const recoveryForm = useForm({ password: '' });
const showDisableForm = ref(false);
const showRecoveryForm = ref(false);
const copiedKey = ref(false);
const copiedCodes = ref(false);

const recoveryCodes = computed(() => props.twoFactor.recoveryCodes ?? []);

function enableTwoFactor() {
    router.post(route('two-factor.enable'), {}, { preserveScroll: true });
}

function confirmTwoFactor() {
    confirmForm.post(route('two-factor.confirm'), { preserveScroll: true });
}

async function disableTwoFactor() {
    if (
        !(await confirmAction({
            title: t('profile.twoFactorDisableTitle'),
            message: t('profile.twoFactorDisableMessage'),
            confirmLabel: t('profile.twoFactorDisableConfirm'),
            variant: 'danger',
        }))
    ) {
        return;
    }

    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            showDisableForm.value = false;
            disableForm.reset();
        },
    });
}

async function regenerateRecoveryCodes() {
    if (
        !(await confirmAction({
            title: t('profile.twoFactorRegenerateTitle'),
            message: t('profile.twoFactorRegenerateMessage'),
            confirmLabel: t('common.confirm'),
            variant: 'danger',
        }))
    ) {
        return;
    }

    recoveryForm.post(route('two-factor.recovery-codes'), {
        preserveScroll: true,
        onSuccess: () => {
            showRecoveryForm.value = false;
            recoveryForm.reset();
        },
    });
}

async function copyManualKey() {
    if (!props.twoFactor.manualKey) {
        return;
    }

    await navigator.clipboard.writeText(props.twoFactor.manualKey);
    copiedKey.value = true;
    window.setTimeout(() => {
        copiedKey.value = false;
    }, 2000);
}

async function copyRecoveryCodes() {
    if (!recoveryCodes.value.length) {
        return;
    }

    await navigator.clipboard.writeText(recoveryCodes.value.join('\n'));
    copiedCodes.value = true;
    window.setTimeout(() => {
        copiedCodes.value = false;
    }, 2000);
}
</script>

<template>
    <div class="space-y-4">
        <!-- Idle: not enabled -->
        <div v-if="!twoFactor.enabled && !twoFactor.pending" class="space-y-4">
            <div class="flex items-start gap-3 rounded-xl border border-line/80 bg-surface-muted/35 p-4">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent ring-1 ring-accent/15"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-ink">{{ t('profile.twoFactorSetupHint') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-ink-muted">{{ t('profile.twoFactorDesc') }}</p>
                </div>
            </div>
            <Button type="button" size="sm" @click="enableTwoFactor">{{ t('profile.twoFactorEnable') }}</Button>
        </div>

        <!-- Pending: scan + confirm -->
        <div v-else-if="twoFactor.pending" class="space-y-5">
            <div class="grid gap-5" :class="compact ? '' : 'lg:grid-cols-[auto,minmax(0,1fr)] lg:items-start'">
                <div class="flex flex-col items-center gap-3 lg:items-start">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
                        {{ t('profile.twoFactorStepScan') }}
                    </p>
                    <div
                        v-if="twoFactor.qrSvg"
                        class="profile-2fa-qr rounded-2xl border border-line bg-white p-3 shadow-sm"
                        v-html="twoFactor.qrSvg"
                    />
                </div>

                <div class="min-w-0 space-y-4">
                    <p class="text-sm leading-relaxed text-ink-muted">{{ t('profile.twoFactorScanHint') }}</p>

                    <div v-if="twoFactor.manualKey" class="rounded-xl border border-line/80 bg-surface-muted/40 p-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-muted">
                                {{ t('profile.twoFactorManualKey') }}
                            </p>
                            <Button type="button" variant="ghost" size="sm" @click="copyManualKey">
                                {{ copiedKey ? t('profile.twoFactorCopied') : t('profile.twoFactorCopyKey') }}
                            </Button>
                        </div>
                        <p class="mt-2 break-all font-mono text-[13px] leading-relaxed tracking-wide text-ink">
                            {{ twoFactor.manualKey }}
                        </p>
                    </div>

                    <form class="space-y-3 border-t border-line/70 pt-4" @submit.prevent="confirmTwoFactor">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
                            {{ t('profile.twoFactorStepVerify') }}
                        </p>
                        <FormField
                            :label="t('profile.twoFactorCode')"
                            html-for="two_factor_code"
                            :error="confirmForm.errors.code"
                        >
                            <Input
                                id="two_factor_code"
                                v-model="confirmForm.code"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="8"
                                class="max-w-[12rem] text-center font-mono text-lg tracking-[0.35em]"
                                :placeholder="t('profile.twoFactorCodePlaceholder')"
                            />
                        </FormField>
                        <div class="flex items-center gap-3">
                            <Button type="submit" size="sm" :disabled="confirmForm.processing">
                                {{ t('profile.twoFactorConfirm') }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enabled -->
        <div v-else class="space-y-4">
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/25">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <p class="text-sm leading-relaxed text-ink-muted">{{ t('profile.twoFactorEnabledHint') }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button type="button" variant="secondary" size="sm" @click="showRecoveryForm = !showRecoveryForm">
                    {{ t('profile.twoFactorRegenerate') }}
                </Button>
                <Button type="button" variant="ghost" size="sm" @click="showDisableForm = !showDisableForm">
                    {{ t('profile.twoFactorDisable') }}
                </Button>
            </div>

            <form
                v-if="showRecoveryForm"
                class="space-y-3 rounded-xl border border-line/80 bg-surface-muted/40 p-4"
                @submit.prevent="regenerateRecoveryCodes"
            >
                <p class="text-xs leading-relaxed text-ink-muted">{{ t('profile.twoFactorRegeneratePasswordHint') }}</p>
                <FormField label-key="password" html-for="recovery_password" :error="recoveryForm.errors.password">
                    <Input
                        id="recovery_password"
                        v-model="recoveryForm.password"
                        type="password"
                        autocomplete="current-password"
                        class="max-w-xs"
                    />
                </FormField>
                <Button type="submit" variant="secondary" size="sm" :disabled="recoveryForm.processing">
                    {{ t('profile.twoFactorRegenerate') }}
                </Button>
            </form>

            <form
                v-if="showDisableForm"
                class="space-y-3 rounded-xl border border-red-200/80 bg-red-50/50 p-4 dark:border-red-900/50 dark:bg-red-950/20"
                @submit.prevent="disableTwoFactor"
            >
                <p class="text-xs leading-relaxed text-ink-muted">{{ t('profile.twoFactorDisablePasswordHint') }}</p>
                <FormField label-key="password" html-for="disable_password" :error="disableForm.errors.password">
                    <Input
                        id="disable_password"
                        v-model="disableForm.password"
                        type="password"
                        autocomplete="current-password"
                        class="max-w-xs"
                    />
                </FormField>
                <Button type="submit" variant="danger" size="sm" :disabled="disableForm.processing">
                    {{ t('profile.twoFactorDisable') }}
                </Button>
            </form>
        </div>

        <!-- Recovery codes (flash after enable/regenerate) -->
        <div
            v-if="recoveryCodes.length"
            class="rounded-xl border border-amber-200/90 bg-amber-50/80 p-4 dark:border-amber-900/50 dark:bg-amber-950/25"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-ink">{{ t('profile.twoFactorRecoveryTitle') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-ink-muted">{{ t('profile.twoFactorRecoveryHint') }}</p>
                </div>
                <Button type="button" variant="secondary" size="sm" @click="copyRecoveryCodes">
                    {{ copiedCodes ? t('profile.twoFactorCopied') : t('profile.twoFactorCopyCodes') }}
                </Button>
            </div>

            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                <li
                    v-for="code in recoveryCodes"
                    :key="code"
                    class="rounded-lg border border-line/70 bg-surface/90 px-3 py-2 font-mono text-[11px] tracking-wide text-ink"
                >
                    {{ code }}
                </li>
            </ul>
        </div>
    </div>
</template>
