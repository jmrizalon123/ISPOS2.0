<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import type { IsposBrandingSettings, TranslationSettings } from '@/types';
import { clearBrandingPreview, setBrandingPreview } from '@/Utils/documentTitle';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    companyId: string | null;
    storeId: string | null;
    registerId: string | null;
    companies: Array<{ id: string; name: string }>;
    stores: Array<{ id: string; store_name: string; company_id: string }>;
    registers: Array<{ id: string; register_name: string; register_code: string; store_id: string }>;
    companySettings: Record<string, unknown>;
    storeSettings: Record<string, unknown>;
    registerSettings: Record<string, unknown>;
    resolved: { receipt_footer: unknown; tax_rate: unknown };
    translationSettings: TranslationSettings;
    isposBrandingSettings: IsposBrandingSettings;
}>();

const { t } = useI18n();
const page = usePage();

const sectionTab = ref('system');
const scopeTab = ref(props.registerId ? 'register' : props.storeId ? 'store' : 'company');
const companyId = ref(props.companyId ?? '');
const storeId = ref(props.storeId ?? '');
const registerId = ref(props.registerId ?? '');

const sectionTabs = computed(() => [
    { key: 'system', label: t('settings.tabSystem'), description: t('settings.tabSystemDesc') },
    { key: 'operations', label: t('settings.tabOperations'), description: t('settings.tabOperationsDesc') },
    { key: 'language', label: t('settings.tabLanguage'), description: t('settings.tabLanguageDesc') },
]);

const activeSectionMeta = computed(() => sectionTabs.value.find((tab) => tab.key === sectionTab.value));

watch([companyId, storeId, registerId], () => {
    router.get(
        route('admin.settings.edit'),
        {
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
            register_id: registerId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

const form = useForm({
    scope: 'company' as 'company' | 'store' | 'register',
    scope_id: companyId.value,
    receipt_footer: String(props.companySettings['company.receipt_footer'] ?? props.resolved.receipt_footer ?? ''),
    tax_rate: String(props.companySettings['company.tax_rate'] ?? props.resolved.tax_rate ?? '12'),
});

const translationForm = useForm({
    provider: props.translationSettings.provider,
    google_enabled: props.translationSettings.google_enabled,
    google_use_widget: props.translationSettings.google_use_widget,
    google_api_key: '',
});

const isposForm = useForm<{
    name: string;
    tagline: string;
    ownership: string;
    copyright: string;
    support_email: string;
    support_url: string;
    logo: File | null;
    remove_logo: boolean;
}>({
    name: props.isposBrandingSettings.name,
    tagline: props.isposBrandingSettings.tagline,
    ownership: props.isposBrandingSettings.ownership,
    copyright: props.isposBrandingSettings.copyright,
    support_email: props.isposBrandingSettings.support_email,
    support_url: props.isposBrandingSettings.support_url,
    logo: null,
    remove_logo: false,
});

const logoPreview = computed(() => {
    if (isposForm.logo) {
        return URL.createObjectURL(isposForm.logo);
    }

    return props.isposBrandingSettings.logo_url;
});

const effectiveLogoPreview = computed(() => (isposForm.remove_logo ? null : logoPreview.value));

const brandInitials = computed(() => {
    const name = isposForm.name.trim();
    if (!name) return 'iS';
    const parts = name.split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
});

function onLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    isposForm.logo = input.files?.[0] ?? null;
    if (isposForm.logo) {
        isposForm.remove_logo = false;
    }
}

watch(
    () => translationForm.provider,
    (provider) => {
        if (provider === 'google') {
            translationForm.google_enabled = true;
        }
    },
);

function settingsForScope(scope: string): Record<string, unknown> {
    if (scope === 'register') return props.registerSettings;
    if (scope === 'store') return props.storeSettings;
    return props.companySettings;
}

function scopeIdForTab(scope: string): string {
    if (scope === 'register') return registerId.value;
    if (scope === 'store') return storeId.value;
    return companyId.value;
}

watch(scopeTab, (value) => {
    form.scope = value as 'company' | 'store' | 'register';
    form.scope_id = scopeIdForTab(value);
    const source = settingsForScope(value);
    form.receipt_footer = String(source['company.receipt_footer'] ?? props.resolved.receipt_footer ?? '');
    form.tax_rate = String(source['company.tax_rate'] ?? props.resolved.tax_rate ?? '12');
});

async function submitOperations() {
    if (!(await confirmSave(t('common.save'), `${scopeTab.value} settings`))) {
        return;
    }

    form.scope = scopeTab.value as 'company' | 'store' | 'register';
    form.scope_id = scopeIdForTab(scopeTab.value);
    form.put(route('admin.settings.update'), { preserveScroll: true });
}

async function submitIspos() {
    if (!(await confirmSave(t('common.save'), t('settings.isposTitle')))) {
        return;
    }

    isposForm.post(route('admin.settings.ispos.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isposForm.logo = null;
            isposForm.remove_logo = false;
            clearBrandingPreview();
        },
    });
}

async function submitTranslation() {
    if (!(await confirmSave(t('common.save'), t('settings.translationTitle')))) {
        return;
    }

    translationForm.put(route('admin.settings.translation.update'), {
        preserveScroll: true,
        onSuccess: () => {
            translationForm.google_api_key = '';
        },
    });
}

const canSaveOperations = () => {
    if (scopeTab.value === 'company') return !!companyId.value;
    if (scopeTab.value === 'store') return !!storeId.value;
    return !!registerId.value;
};

watch(
    [() => isposForm.name, effectiveLogoPreview, sectionTab],
    ([name, logo, tab]) => {
        if (tab !== 'system') {
            clearBrandingPreview();

            return;
        }

        setBrandingPreview({ appName: name, logoUrl: logo });
    },
    { immediate: true },
);

onUnmounted(() => {
    clearBrandingPreview();
});
</script>

<template>
    <Head :title="t('settings.title')" />
    <AppLayout>
        <template #header>{{ t('settings.title') }}</template>
        <template #subheader>{{ t('settings.subheader') }}</template>

        <FormShell
            :title="t('settings.title')"
            :subtitle="t('settings.subheader')"
            :eyebrow="t('subheaders.administration')"
            :back-href="route('dashboard')"
            :section-label="activeSectionMeta?.label"
            :section-description="activeSectionMeta?.description"
        >
            <Alert v-if="page.props.flash?.success" variant="success" class="mx-4 mt-4 sm:mx-5 lg:mx-6">
                {{ page.props.flash.success }}
            </Alert>

            <FormTabs v-model="sectionTab" :tabs="sectionTabs">
                <template #default="{ active }">
                    <!-- System / iSPOS -->
                    <div v-show="active === 'system'" class="form-tab-panel">
                        <form @submit.prevent="submitIspos">
                            <div class="grid gap-6 xl:grid-cols-[minmax(0,17rem)_minmax(0,1fr)]">
                                <aside class="space-y-4">
                                    <div class="overflow-hidden rounded-2xl border border-line bg-zinc-950 text-white shadow-panel">
                                        <div class="border-b border-white/10 px-4 py-3">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                                                {{ t('settings.brandPreview') }}
                                            </p>
                                        </div>
                                        <div class="space-y-5 p-5">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-white/10 text-sm font-bold"
                                                >
                                                    <img
                                                        v-if="effectiveLogoPreview"
                                                        :src="effectiveLogoPreview"
                                                        alt=""
                                                        class="h-full w-full object-contain p-1.5"
                                                    />
                                                    <span v-else>{{ brandInitials }}</span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-display text-base font-bold">{{ isposForm.name || 'iSPOS' }}</p>
                                                    <p class="truncate text-xs text-zinc-400">Backoffice</p>
                                                </div>
                                            </div>
                                            <p class="text-sm leading-relaxed text-zinc-400">
                                                {{ isposForm.tagline || t('settings.taglinePlaceholder') }}
                                            </p>
                                            <p v-if="isposForm.ownership" class="text-xs text-zinc-500">{{ isposForm.ownership }}</p>
                                            <p class="border-t border-white/10 pt-3 text-[11px] text-zinc-600">
                                                {{ isposForm.copyright || `© ${new Date().getFullYear()} iSPOS` }}
                                            </p>
                                        </div>
                                    </div>
                                </aside>

                                <div class="space-y-6">
                                    <FormSection :title="t('settings.brandIdentity')">
                                        <div class="form-grid">
                                            <div class="sm:col-span-2">
                                                <label class="ui-label">{{ t('settings.logo') }}</label>
                                                <label
                                                    class="mt-1.5 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-line bg-surface-muted/40 px-4 py-6 transition hover:border-accent/40 hover:bg-accent-soft/20"
                                                >
                                                    <span class="text-sm font-medium text-ink">{{ t('settings.logoUpload') }}</span>
                                                    <span class="mt-1 text-xs text-ink-muted">{{ t('settings.logoHelp') }}</span>
                                                    <input
                                                        type="file"
                                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                        class="sr-only"
                                                        @change="onLogoChange"
                                                    />
                                                </label>
                                                <InputError :message="isposForm.errors.logo" />
                                                <label
                                                    v-if="isposBrandingSettings.logo_url"
                                                    class="mt-3 flex items-center gap-2 text-sm text-ink"
                                                >
                                                    <input v-model="isposForm.remove_logo" type="checkbox" class="rounded border-line" />
                                                    {{ t('settings.removeLogo') }}
                                                </label>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="ui-label">{{ t('settings.systemName') }}</label>
                                                <Input v-model="isposForm.name" :placeholder="t('settings.systemNamePlaceholder')" />
                                                <InputError :message="isposForm.errors.name" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="ui-label">{{ t('settings.tagline') }}</label>
                                                <Input v-model="isposForm.tagline" :placeholder="t('settings.taglinePlaceholder')" />
                                                <InputError :message="isposForm.errors.tagline" />
                                            </div>
                                        </div>
                                    </FormSection>

                                    <FormSection :title="t('settings.contactInfo')">
                                        <div class="form-grid">
                                            <div>
                                                <label class="ui-label">{{ t('settings.ownership') }}</label>
                                                <Input v-model="isposForm.ownership" :placeholder="t('settings.ownershipPlaceholder')" />
                                                <InputError :message="isposForm.errors.ownership" />
                                            </div>
                                            <div>
                                                <label class="ui-label">{{ t('settings.copyright') }}</label>
                                                <Input v-model="isposForm.copyright" :placeholder="t('settings.copyrightPlaceholder')" />
                                                <InputError :message="isposForm.errors.copyright" />
                                            </div>
                                            <div>
                                                <label class="ui-label">{{ t('settings.supportEmail') }}</label>
                                                <Input v-model="isposForm.support_email" type="email" />
                                                <InputError :message="isposForm.errors.support_email" />
                                            </div>
                                            <div>
                                                <label class="ui-label">{{ t('settings.supportUrl') }}</label>
                                                <Input v-model="isposForm.support_url" type="url" />
                                                <InputError :message="isposForm.errors.support_url" />
                                            </div>
                                        </div>
                                    </FormSection>
                                </div>
                            </div>

                            <div class="form-action-bar mt-6">
                                <Button type="submit" :disabled="isposForm.processing">{{ t('settings.saveIspos') }}</Button>
                            </div>
                        </form>
                    </div>

                    <!-- Operations -->
                    <div v-show="active === 'operations'" class="form-tab-panel">
                        <form @submit.prevent="submitOperations">
                            <FormSection :title="t('settings.scopeTitle')" :description="t('settings.scopeDesc')">
                                <div class="form-grid sm:grid-cols-3">
                                    <div>
                                        <label class="ui-label">{{ t('settings.company') }}</label>
                                        <Select v-model="companyId">
                                            <option value="">{{ t('settings.selectCompany') }}</option>
                                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                                {{ company.name }}
                                            </option>
                                        </Select>
                                    </div>
                                    <div>
                                        <label class="ui-label">{{ t('settings.store') }}</label>
                                        <Select v-model="storeId">
                                            <option value="">{{ t('settings.allStores') }}</option>
                                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                                {{ store.store_name }}
                                            </option>
                                        </Select>
                                    </div>
                                    <div>
                                        <label class="ui-label">{{ t('settings.register') }}</label>
                                        <Select v-model="registerId">
                                            <option value="">{{ t('settings.allRegisters') }}</option>
                                            <option v-for="register in registers" :key="register.id" :value="register.id">
                                                {{ register.register_name }} ({{ register.register_code }})
                                            </option>
                                        </Select>
                                    </div>
                                </div>
                            </FormSection>

                            <Alert class="mt-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ t('settings.hierarchyNote') }}</span>
                                    <Badge variant="neutral">
                                        {{ t('settings.resolved') }}: “{{ resolved.receipt_footer }}” · {{ resolved.tax_rate }}%
                                    </Badge>
                                </div>
                            </Alert>

                            <div class="mt-6 rounded-2xl border border-line/80 bg-surface-muted/30 p-4 sm:p-5">
                                <Tabs
                                    v-model="scopeTab"
                                    variant="pill"
                                    :tabs="[
                                        { key: 'company', label: t('settings.company'), description: t('settings.scopeCompanyDesc') },
                                        { key: 'store', label: t('settings.store'), description: t('settings.scopeStoreDesc') },
                                        { key: 'register', label: t('settings.register'), description: t('settings.scopeRegisterDesc') },
                                    ]"
                                >
                                    <div class="form-grid pt-1">
                                        <div class="sm:col-span-2">
                                            <label class="ui-label">{{ t('settings.receiptFooter') }}</label>
                                            <Input
                                                v-model="form.receipt_footer"
                                                :placeholder="t('settings.receiptFooterPlaceholder')"
                                            />
                                            <InputError :message="form.errors.receipt_footer" />
                                        </div>
                                        <div>
                                            <label class="ui-label">{{ t('settings.taxRate') }}</label>
                                            <Input v-model="form.tax_rate" type="number" step="0.01" />
                                            <InputError :message="form.errors.tax_rate" />
                                        </div>
                                    </div>
                                </Tabs>
                            </div>

                            <div class="form-action-bar mt-6">
                                <Button type="submit" :disabled="form.processing || !canSaveOperations()">
                                    {{ t('settings.saveSettings') }}
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Language -->
                    <div v-show="active === 'language'" class="form-tab-panel">
                        <form @submit.prevent="submitTranslation">
                            <FormSection
                                :title="t('settings.translationTitle')"
                                :description="t('settings.translationSubheader')"
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label
                                        class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition hover:border-accent/40 hover:bg-accent-soft/20"
                                        :class="
                                            translationForm.provider === 'builtin'
                                                ? 'border-accent bg-accent-soft/40 ring-1 ring-accent/20'
                                                : ''
                                        "
                                    >
                                        <input v-model="translationForm.provider" type="radio" class="mt-1" value="builtin" />
                                        <span>
                                            <span class="block text-sm font-semibold text-ink">{{ t('settings.providerBuiltin') }}</span>
                                            <span class="mt-0.5 block text-xs text-ink-muted">{{ t('settings.providerBuiltinDesc') }}</span>
                                        </span>
                                    </label>
                                    <label
                                        class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition hover:border-accent/40 hover:bg-accent-soft/20"
                                        :class="
                                            translationForm.provider === 'google'
                                                ? 'border-accent bg-accent-soft/40 ring-1 ring-accent/20'
                                                : ''
                                        "
                                    >
                                        <input v-model="translationForm.provider" type="radio" class="mt-1" value="google" />
                                        <span>
                                            <span class="block text-sm font-semibold text-ink">{{ t('settings.providerGoogle') }}</span>
                                            <span class="mt-0.5 block text-xs text-ink-muted">{{ t('settings.providerGoogleDesc') }}</span>
                                        </span>
                                    </label>
                                </div>
                                <InputError :message="translationForm.errors.provider" />
                            </FormSection>

                            <FormSection
                                v-if="translationForm.provider === 'google'"
                                :title="t('settings.googleOptionsTitle')"
                                class="mt-6"
                            >
                                <div class="space-y-4">
                                    <label
                                        class="form-toggle"
                                        :class="{ 'form-toggle--on': translationForm.google_enabled }"
                                    >
                                        <input v-model="translationForm.google_enabled" type="checkbox" class="sr-only" />
                                        <span class="form-toggle-track" aria-hidden="true">
                                            <span class="form-toggle-thumb" />
                                        </span>
                                        <span class="form-toggle-text">
                                            <span class="form-toggle-label">{{ t('settings.googleEnabled') }}</span>
                                        </span>
                                    </label>

                                    <label
                                        class="form-toggle"
                                        :class="{ 'form-toggle--on': translationForm.google_use_widget }"
                                    >
                                        <input v-model="translationForm.google_use_widget" type="checkbox" class="sr-only" />
                                        <span class="form-toggle-track" aria-hidden="true">
                                            <span class="form-toggle-thumb" />
                                        </span>
                                        <span class="form-toggle-text">
                                            <span class="form-toggle-label">{{ t('settings.googleUseWidget') }}</span>
                                            <span class="form-toggle-hint">{{ t('settings.googleUseWidgetHelp') }}</span>
                                        </span>
                                    </label>

                                    <div>
                                        <label class="ui-label">{{ t('settings.googleApiKey') }}</label>
                                        <Input
                                            v-model="translationForm.google_api_key"
                                            type="password"
                                            autocomplete="off"
                                            :placeholder="
                                                translationSettings.has_api_key
                                                    ? '••••••••••••'
                                                    : t('settings.googleApiKeyPlaceholder')
                                            "
                                        />
                                        <p class="mt-1 text-xs text-ink-muted">{{ t('settings.googleApiKeyHelp') }}</p>
                                        <InputError :message="translationForm.errors.google_api_key" />
                                    </div>
                                </div>
                            </FormSection>

                            <div class="form-action-bar mt-6">
                                <Button type="submit" :disabled="translationForm.processing">
                                    {{ t('settings.saveTranslation') }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </template>
            </FormTabs>
        </FormShell>
    </AppLayout>
</template>
