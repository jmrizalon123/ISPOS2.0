<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useLocale();
const page = usePage();

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    twoFactor: {
        enabled: boolean;
        pending: boolean;
        qrSvg: string | null;
        manualKey: string | null;
        recoveryCodes: string[] | null;
    };
}>();

const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles ?? []);

const twoFactorStatus = computed(() => {
    if (props.twoFactor.enabled) {
        return { label: t('profile.enabled'), variant: 'success' as const };
    }

    if (props.twoFactor.pending) {
        return { label: t('profile.twoFactorPending'), variant: 'warning' as const };
    }

    return { label: t('profile.notEnabled'), variant: 'neutral' as const };
});

const twoFactorTitle = computed(() =>
    props.twoFactor.pending ? t('profile.twoFactorSetupTitle') : t('profile.twoFactor'),
);

const twoFactorDescription = computed(() =>
    props.twoFactor.pending ? t('profile.twoFactorSetupDesc') : t('profile.twoFactorDesc'),
);
</script>

<template>
    <Head :title="t('profile.title')" />
    <AppLayout>
        <template #header>{{ t('profile.title') }}</template>
        <template #subheader>{{ user?.email }}</template>

        <div class="mx-auto grid w-full max-w-6xl gap-4 lg:grid-cols-2 lg:items-start">
            <!-- Account panel -->
            <aside class="ui-panel flex flex-col divide-y divide-line/80">
                <div class="flex flex-wrap items-center gap-4 p-5 sm:p-6">
                    <Avatar :src="user?.avatar_url" :name="user?.name" size="lg" />

                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
                            {{ t('profile.eyebrow') }}
                        </p>
                        <h2 class="mt-0.5 truncate font-display text-xl font-bold tracking-tight text-ink">
                            {{ user?.name }}
                        </h2>
                        <p class="truncate text-sm text-ink-muted">{{ user?.email }}</p>

                        <div v-if="roles.length" class="mt-2.5 flex flex-wrap gap-1">
                            <Badge v-for="role in roles" :key="role" variant="accent">{{ role }}</Badge>
                        </div>
                    </div>

                    <div v-if="user?.company" class="ui-panel-muted min-w-[10rem] px-3.5 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-muted">
                            {{ t('fields.company') }}
                        </p>
                        <p class="mt-0.5 truncate text-sm font-semibold text-ink">
                            {{ user.company.display_name || user.company.name }}
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="mb-5">
                        <h3 class="font-display text-base font-semibold tracking-tight text-ink">
                            {{ t('profile.information') }}
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                            {{ t('profile.informationDesc') }}
                        </p>
                    </div>
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </div>

                <div id="password" class="scroll-mt-24 p-5 sm:p-6">
                    <div class="mb-5">
                        <h3 class="font-display text-base font-semibold tracking-tight text-ink">
                            {{ t('profile.password') }}
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                            {{ t('profile.passwordDesc') }}
                        </p>
                    </div>
                    <UpdatePasswordForm />
                </div>
            </aside>

            <!-- Security panel -->
            <section class="ui-panel flex flex-col divide-y divide-line/80">
                <div id="two-factor" class="scroll-mt-24 p-5 sm:p-6">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-display text-base font-semibold tracking-tight text-ink">
                                {{ twoFactorTitle }}
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                                {{ twoFactorDescription }}
                            </p>
                        </div>
                        <Badge class="shrink-0" :variant="twoFactorStatus.variant">
                            {{ twoFactorStatus.label }}
                        </Badge>
                    </div>
                    <TwoFactorAuthenticationForm :two-factor="twoFactor" />
                </div>

                <div class="p-5 sm:p-6">
                    <div class="mb-5">
                        <h3 class="font-display text-base font-semibold tracking-tight text-ink">
                            {{ t('profile.deleteAccount') }}
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                            {{ t('profile.deleteAccountDesc') }}
                        </p>
                    </div>
                    <DeleteUserForm />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
