<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormMetaRow from '@/Components/forms/FormMetaRow.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Input from '@/Components/ui/Input.vue';
import InputError from '@/Components/InputError.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import { defaultUserForm, type UserFormData, type UserRecord } from '@/types/userForm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

const page = useModulePage('users');
const { t, placeholder, submit, tab, tabDesc } = useLocale();

const props = defineProps<{
    user: UserRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; company_id: string; store_type?: string | null }>;
    warehouses: Array<{ id: string; store_name: string; company_id: string }>;
    departments: Array<{ id: string; name: string; code: string | null; company_id: string }>;
    positions: Array<{ id: string; name: string; code: string | null; company_id: string; department_id: string | null }>;
    roles: string[];
}>();

const activeTab = ref('identity');
const isEdit = computed(() => !!props.user);
const pageTitle = useFormPageTitle('user', isEdit);

const tabs = computed(() => [
    { key: 'identity', label: tab('identity'), description: tabDesc('userIdentity') },
    { key: 'profile', label: tab('profile'), description: tabDesc('userProfile') },
    { key: 'employment', label: tab('employment'), description: tabDesc('userEmployment') },
    { key: 'location', label: tab('location'), description: tabDesc('userLocation') },
    { key: 'access', label: tab('access'), description: tabDesc('userAccess') },
    { key: 'preferences', label: tab('preferences'), description: tabDesc('userPreferences') },
    ...(isEdit.value ? [{ key: 'security', label: tab('security'), description: tabDesc('userSecurity') }] : []),
]);

const form = useForm<UserFormData>(
    defaultUserForm(props.user, props.user?.company_id ?? props.companies[0]?.id ?? ''),
);

const activeTabMeta = computed(() => tabs.value.find((item) => item.key === activeTab.value) ?? tabs.value[0]);

const tabErrorConfig: FormTabErrorConfig[] = [
    { key: 'identity', requiredFields: ['email'], errorPrefixes: ['username', 'employee_id', 'phone', 'password'] },
    { key: 'profile', errorPrefixes: ['first_name', 'middle_name', 'last_name', 'suffix', 'display_name', 'avatar', 'remove_avatar'] },
    { key: 'employment', errorPrefixes: ['company_id', 'department_id', 'position_id'] },
    { key: 'location', requiredFields: ['base_type'], errorPrefixes: ['default_store_id', 'default_warehouse_id'] },
    { key: 'access', requiredFields: ['status'], errorPrefixes: ['roles', 'store_ids', 'is_active', 'is_locked'] },
    { key: 'preferences', errorPrefixes: ['language', 'timezone'] },
];

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

const companyStores = computed(() =>
    props.stores.filter((store) => !form.company_id || store.company_id === form.company_id),
);

const companyWarehouses = computed(() =>
    props.warehouses.filter((warehouse) => !form.company_id || warehouse.company_id === form.company_id),
);

const companyDepartments = computed(() =>
    props.departments.filter((department) => !form.company_id || department.company_id === form.company_id),
);

const companyPositions = computed(() =>
    props.positions.filter((position) => {
        if (form.company_id && position.company_id !== form.company_id) {
            return false;
        }

        if (form.department_id && position.department_id && position.department_id !== form.department_id) {
            return false;
        }

        return true;
    }),
);

watch(
    () => form.base_type,
    (value) => {
        if (value === 'head_office') {
            form.default_store_id = '';
            form.default_warehouse_id = '';
        } else if (value === 'store') {
            form.default_warehouse_id = '';
        } else if (value === 'warehouse') {
            form.default_store_id = '';
        }
    },
);

watch(
    () => form.company_id,
    () => {
        if (form.default_store_id && !companyStores.value.some((store) => store.id === form.default_store_id)) {
            form.default_store_id = '';
        }

        if (form.default_warehouse_id && !companyWarehouses.value.some((warehouse) => warehouse.id === form.default_warehouse_id)) {
            form.default_warehouse_id = '';
        }

        if (form.department_id && !companyDepartments.value.some((department) => department.id === form.department_id)) {
            form.department_id = '';
        }

        if (form.position_id && !companyPositions.value.some((position) => position.id === form.position_id)) {
            form.position_id = '';
        }
    },
);

function fieldError(key: keyof UserFormData): string | undefined {
    return form.errors[key];
}

const selectedAvatarPreview = ref<string | null>(null);

const avatarPreview = computed(() => {
    if (selectedAvatarPreview.value) {
        return selectedAvatarPreview.value;
    }

    return form.remove_avatar ? null : props.user?.avatar_url ?? null;
});

const avatarName = computed(() => {
    const fullName = [form.first_name, form.last_name].filter(Boolean).join(' ').trim();

    return form.display_name.trim() || fullName || props.user?.name || form.email;
});

function releaseAvatarPreview() {
    if (selectedAvatarPreview.value) {
        URL.revokeObjectURL(selectedAvatarPreview.value);
        selectedAvatarPreview.value = null;
    }
}

function onAvatarChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    releaseAvatarPreview();
    form.avatar = file;

    if (file) {
        selectedAvatarPreview.value = URL.createObjectURL(file);
        form.remove_avatar = false;
    }
}

onUnmounted(releaseAvatarPreview);

function toggleRole(role: string) {
    if (form.roles.includes(role)) {
        form.roles = form.roles.filter((item) => item !== role);
    } else {
        form.roles = [...form.roles, role];
    }
}

function toggleStore(id: string) {
    if (form.store_ids.includes(id)) {
        form.store_ids = form.store_ids.filter((item) => item !== id);
    } else {
        form.store_ids = [...form.store_ids, id];
    }
}

function formatDateTime(value: string | null | undefined): string {
    return value ? new Date(value).toLocaleString() : '—';
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.user'),
    );
    if (!confirmed) return;

    const hasUpload = form.avatar instanceof File;

    form.transform((data) => {
        const payload: Record<string, unknown> = { ...data };

        if (!(payload.avatar instanceof File)) {
            delete payload.avatar;
        }

        // PHP only parses multipart bodies on POST, so file uploads spoof the PUT method.
        if (isEdit.value && hasUpload) {
            payload._method = 'put';
        }

        return payload;
    });

    if (isEdit.value) {
        if (hasUpload) {
            form.post(route('admin.users.update', props.user!.id), { forceFormData: true });
        } else {
            form.put(route('admin.users.update', props.user!.id));
        }
    } else {
        form.post(route('admin.users.store'), hasUpload ? { forceFormData: true } : {});
    }
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="users"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.users.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                    <template #default="{ active }">
                    <div v-show="active === 'identity'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField v-if="isEdit" label-key="uuid">
                                    <Input :model-value="user?.uuid ?? '—'" readonly />
                                </FormField>
                                <FormField label-key="employeeId" :error="fieldError('employee_id')">
                                    <Input v-model="form.employee_id" :placeholder="placeholder('employeeId')" />
                                </FormField>
                                <FormField label-key="username" :error="fieldError('username')">
                                    <Input v-model="form.username" autocomplete="username" />
                                </FormField>
                                <FormField label-key="email" required :error="fieldError('email')">
                                    <Input v-model="form.email" type="email" autocomplete="email" :placeholder="placeholder('email')" />
                                </FormField>
                                <FormField label-key="phone" :error="fieldError('phone')">
                                    <Input v-model="form.phone" type="tel" />
                                </FormField>
                                <FormField label-key="password" :error="fieldError('password')">
                                    <Input
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        :placeholder="user ? placeholder('leaveBlankKeep') : placeholder('password')"
                                    />
                                </FormField>
                                <FormField label-key="confirmPassword" :error="fieldError('password_confirmation')">
                                    <Input v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                                </FormField>
                            </div>
                        </FormSection>
                    </div>

                    <div v-show="active === 'profile'" class="form-tab-panel">
                        <FormSection>
                            <div class="mb-5 flex flex-wrap items-center gap-4">
                                <Avatar :src="avatarPreview" :name="avatarName" size="lg" />
                                <div class="min-w-[16rem] flex-1">
                                    <span class="ui-label">{{ t('fields.avatar') }}</span>
                                    <label
                                        class="mt-1.5 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-line bg-surface-muted/40 px-4 py-4 transition hover:border-accent/40 hover:bg-accent-soft/20"
                                    >
                                        <span class="text-sm font-medium text-ink">{{ t('hints.avatarUpload') }}</span>
                                        <span class="mt-1 text-xs text-ink-muted">{{ t('hints.avatarHelp') }}</span>
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            class="sr-only"
                                            @change="onAvatarChange"
                                        />
                                    </label>
                                    <InputError :message="fieldError('avatar')" />
                                    <label
                                        v-if="user?.avatar_url"
                                        class="mt-2 flex items-center gap-2 text-sm text-ink"
                                    >
                                        <input v-model="form.remove_avatar" type="checkbox" class="rounded border-line" />
                                        {{ t('hints.removeAvatar') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-grid">
                                <FormField label-key="firstName" :error="fieldError('first_name')">
                                    <Input v-model="form.first_name" />
                                </FormField>
                                <FormField label-key="middleName" :error="fieldError('middle_name')">
                                    <Input v-model="form.middle_name" />
                                </FormField>
                                <FormField label-key="lastName" :error="fieldError('last_name')">
                                    <Input v-model="form.last_name" />
                                </FormField>
                                <FormField label-key="suffix" :error="fieldError('suffix')">
                                    <Input v-model="form.suffix" :placeholder="placeholder('suffix')" />
                                </FormField>
                                <FormField label-key="displayName" :error="fieldError('display_name')">
                                    <Input v-model="form.display_name" />
                                </FormField>
                            </div>
                        </FormSection>
                    </div>

                    <div v-show="active === 'employment'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField label-key="company" :error="fieldError('company_id')">
                                    <Select v-model="form.company_id">
                                        <option value="">{{ t('fields.none') }}</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.display_name || company.name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField label-key="department" :error="fieldError('department_id')">
                                    <Select v-model="form.department_id">
                                        <option value="">{{ t('fields.none') }}</option>
                                        <option v-for="department in companyDepartments" :key="department.id" :value="department.id">
                                            {{ department.name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField label-key="position" :error="fieldError('position_id')">
                                    <Select v-model="form.position_id">
                                        <option value="">{{ t('fields.none') }}</option>
                                        <option v-for="position in companyPositions" :key="position.id" :value="position.id">
                                            {{ position.name }}
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                        </FormSection>
                    </div>

                    <div v-show="active === 'location'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField label-key="baseType" required :error="fieldError('base_type')">
                                    <Select v-model="form.base_type">
                                        <option value="head_office">{{ t('fields.baseTypeHeadOffice') }}</option>
                                        <option value="store">{{ t('fields.baseTypeStore') }}</option>
                                        <option value="warehouse">{{ t('fields.baseTypeWarehouse') }}</option>
                                    </Select>
                                </FormField>
                                <FormField
                                    v-if="form.base_type === 'store'"
                                    label-key="defaultStore"
                                    :error="fieldError('default_store_id')"
                                >
                                    <Select v-model="form.default_store_id">
                                        <option value="">{{ t('fields.selectStore') }}</option>
                                        <option v-for="store in companyStores" :key="store.id" :value="store.id">
                                            {{ store.store_name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField
                                    v-if="form.base_type === 'warehouse'"
                                    label-key="defaultWarehouse"
                                    :error="fieldError('default_warehouse_id')"
                                >
                                    <Select v-model="form.default_warehouse_id">
                                        <option value="">{{ t('fields.selectWarehouse') }}</option>
                                        <option v-for="warehouse in companyWarehouses" :key="warehouse.id" :value="warehouse.id">
                                            {{ warehouse.store_name }}
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                            <p v-if="form.base_type === 'head_office'" class="mt-3 text-sm text-ink-muted">
                                {{ t('hints.headOfficeBase') }}
                            </p>
                        </FormSection>
                    </div>

                    <div v-show="active === 'access'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField label-key="status" required :error="fieldError('status')">
                                    <Select v-model="form.status">
                                        <option value="active">{{ t('common.active') }}</option>
                                        <option value="inactive">{{ t('common.inactive') }}</option>
                                    </Select>
                                </FormField>
                                <FormField label-key="isActive">
                                    <FormToggle v-model="form.is_active" :label="t('fields.isActive')" />
                                </FormField>
                                <FormField label-key="isLocked">
                                    <FormToggle v-model="form.is_locked" :label="t('fields.isLocked')" />
                                </FormField>
                            </div>

                            <div class="mt-4">
                                <span class="mb-2 block text-sm font-medium">{{ t('fields.roles') }}</span>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="role in roles"
                                        :key="role"
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs"
                                        :class="form.roles.includes(role) ? 'border-accent bg-accent-soft text-accent' : 'border-line text-ink-muted'"
                                        @click="toggleRole(role)"
                                    >
                                        {{ role }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4">
                                <span class="mb-2 block text-sm font-medium">{{ t('fields.storeAccess') }}</span>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label
                                        v-for="store in companyStores"
                                        :key="store.id"
                                        class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.store_ids.includes(store.id)"
                                            class="rounded border-line text-accent focus:ring-accent"
                                            @change="toggleStore(store.id)"
                                        />
                                        {{ store.store_name }}
                                    </label>
                                </div>
                            </div>
                        </FormSection>
                    </div>

                    <div v-show="active === 'preferences'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField label-key="language" :error="fieldError('language')">
                                    <Select v-model="form.language">
                                        <option value="en">English</option>
                                        <option value="fil">Filipino</option>
                                        <option value="zh-CN">中文</option>
                                    </Select>
                                </FormField>
                                <FormField label-key="timezone" :error="fieldError('timezone')">
                                    <Input v-model="form.timezone" />
                                </FormField>
                            </div>
                        </FormSection>
                    </div>

                    <div v-if="isEdit" v-show="active === 'security'" class="form-tab-panel">
                        <FormSection>
                            <div class="form-grid">
                                <FormField label-key="emailVerifiedAt">
                                    <Input :model-value="formatDateTime(user?.email_verified_at)" readonly />
                                </FormField>
                                <FormField label-key="passwordChangedAt">
                                    <Input :model-value="formatDateTime(user?.password_changed_at)" readonly />
                                </FormField>
                                <FormField label-key="twoFactorEnabled">
                                    <Input :model-value="user?.two_factor_enabled ? 'Yes' : 'No'" readonly />
                                </FormField>
                                <FormField label-key="failedLoginAttempts">
                                    <Input :model-value="String(user?.failed_login_attempts ?? 0)" readonly />
                                </FormField>
                                <FormField label-key="lockedAt">
                                    <Input :model-value="formatDateTime(user?.locked_at)" readonly />
                                </FormField>
                                <FormField label-key="lastLoginAt">
                                    <Input :model-value="formatDateTime(user?.last_login_at)" readonly />
                                </FormField>
                                <FormField label-key="lastLoginIp">
                                    <Input :model-value="user?.last_login_ip ?? '—'" readonly />
                                </FormField>
                                <FormField label-key="lastActivityAt">
                                    <Input :model-value="formatDateTime(user?.last_activity_at)" readonly />
                                </FormField>
                            </div>
                            <FormMetaRow
                                class="mt-4"
                                :items="[
                                    { label: t('fields.created'), value: user?.creator?.name ? `${user.creator.name} · ${formatDateTime(user.created_at)}` : formatDateTime(user?.created_at) },
                                    { label: t('fields.lastUpdated'), value: user?.updater?.name ? `${user.updater.name} · ${formatDateTime(user.updated_at)}` : formatDateTime(user?.updated_at) },
                                ]"
                            />
                        </FormSection>
                    </div>
                    </template>
                </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.users.index')"
                    :submit-label="isEdit ? submit('saveUser') : submit('createUser')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
