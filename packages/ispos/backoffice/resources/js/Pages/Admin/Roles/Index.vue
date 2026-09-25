<script setup lang="ts">
import RolePermissionsPanel from '@/Components/admin/roles/RolePermissionsPanel.vue';
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import Input from '@/Components/ui/Input.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmDelete, confirmSave } from '@/Composables/useConfirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';

const page = useModulePage('roles');
const { t } = useI18n();

const props = defineProps<{
    roles: Array<{
        id: number;
        name: string;
        users_count: number;
        permissions_count: number;
        permissions: string[];
    }>;
    selectedRoleId: number | null;
    permissions: string[];
    filters: { role_id: string };
    canUpdate: boolean;
    canDelete: boolean;
    canCreate: boolean;
}>();

const roleSearch = ref('');

const countLabel = useRecordCountLabel(() => props.roles.length, 'role');

const selectedRole = computed(
    () => props.roles.find((role) => role.id === props.selectedRoleId) ?? null,
);

const filteredRoles = computed(() => {
    const term = roleSearch.value.trim().toLowerCase();

    if (!term) {
        return props.roles;
    }

    return props.roles.filter((role) => role.name.toLowerCase().includes(term));
});

const isSystemRoleName = computed(
    () => selectedRole.value?.name === 'Super Admin' || selectedRole.value?.name === 'Developer',
);

const canDeleteSelectedRole = computed(() => {
    if (!props.canDelete || !selectedRole.value) {
        return false;
    }

    return !['Super Admin', 'Developer', 'Company Admin', 'Cashier'].includes(selectedRole.value.name);
});

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function syncFormFromSelectedRole() {
    const role = selectedRole.value;

    form.name = role?.name ?? '';
    form.permissions = role ? [...role.permissions] : [];
    form.clearErrors();
}

watch(() => props.selectedRoleId, syncFormFromSelectedRole, { immediate: true });

watch(
    () => selectedRole.value?.permissions,
    () => syncFormFromSelectedRole(),
);

const isDirty = computed(() => {
    const role = selectedRole.value;

    if (!role) {
        return false;
    }

    const currentPermissions = [...form.permissions].sort().join('|');
    const savedPermissions = [...role.permissions].sort().join('|');

    return form.name !== role.name || currentPermissions !== savedPermissions;
});

function selectRole(roleId: number) {
    router.get(
        route('admin.roles.index'),
        { role_id: roleId },
        { preserveState: true, replace: true },
    );
}

async function saveRole() {
    if (!props.selectedRoleId || !props.canUpdate) {
        return;
    }

    const confirmed = await confirmSave(t('rolesPanel.savePermissions'), t('entities.role'));

    if (!confirmed) {
        return;
    }

    form.put(route('admin.roles.update', props.selectedRoleId), {
        preserveScroll: true,
    });
}

async function destroyRole() {
    if (!selectedRole.value || !canDeleteSelectedRole.value) {
        return;
    }

    if (!(await confirmDelete(`role “${selectedRole.value.name}”`))) {
        return;
    }

    router.delete(route('admin.roles.destroy', selectedRole.value.id));
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page index-page--fill flex flex-col gap-2">
            <IndexPageHeader
                class="shrink-0"
                :back-href="route('admin.users.role-members.index')"
                :back-label="t('pages.roleMembers.title')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template #actions>
                    <Link v-if="canCreate" :href="route('admin.roles.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <EmptyState
                v-if="!roles.length"
                class="flex-1"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="canCreate ? page.newButton : undefined"
                @action="router.visit(route('admin.roles.create'))"
            />

            <div v-else class="grid min-h-0 flex-1 gap-3 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="ui-panel flex flex-col overflow-hidden">
                    <div class="border-b border-line px-3 py-2">
                        <h2 class="text-sm font-semibold text-ink">{{ t('rolesPanel.rolesPanel') }}</h2>
                        <Input
                            v-model="roleSearch"
                            class="mt-1.5"
                            :placeholder="t('pages.roles.searchPlaceholder')"
                        />
                    </div>
                    <div class="flex-1 overflow-y-auto p-2">
                        <button
                            v-for="role in filteredRoles"
                            :key="role.id"
                            type="button"
                            class="mb-1 flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm transition"
                            :class="
                                role.id === selectedRoleId
                                    ? 'bg-accent text-white shadow-sm dark:text-slate-950'
                                    : 'text-ink hover:bg-surface-muted'
                            "
                            @click="selectRole(role.id)"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ role.name }}</span>
                                <span
                                    class="block truncate text-xs"
                                    :class="role.id === selectedRoleId ? 'text-white/80 dark:text-slate-700' : 'text-ink-muted'"
                                >
                                    {{ t('roleMembers.permissionCount', { count: role.permissions_count }) }}
                                </span>
                            </span>
                            <Badge
                                :variant="role.id === selectedRoleId ? 'neutral' : 'accent'"
                                class="shrink-0 tabular-nums"
                            >
                                {{ role.users_count }}
                            </Badge>
                        </button>
                        <p v-if="!filteredRoles.length" class="px-3 py-6 text-center text-sm text-ink-muted">
                            {{ t('pages.roles.emptyTitle') }}
                        </p>
                    </div>
                </aside>

                <section class="ui-panel flex flex-col overflow-hidden">
                    <div v-if="selectedRoleId" class="shrink-0 border-b border-line">
                        <div class="flex items-start justify-between gap-3 px-3 py-2.5">
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-sm font-semibold text-ink">
                                    {{ t('rolesPanel.permissionsPanel') }}
                                </h2>
                                <div class="mt-2 max-w-md">
                                    <Input
                                        v-model="form.name"
                                        :disabled="!canUpdate || isSystemRoleName"
                                        :placeholder="t('fields.name')"
                                    />
                                    <p v-if="isSystemRoleName" class="mt-1 text-xs text-ink-muted">
                                        {{ t('rolesPanel.systemRoleNameLocked') }}
                                    </p>
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <Link
                                    :href="route('admin.users.role-members.index', { role_id: selectedRoleId })"
                                >
                                    <Button variant="secondary">{{ t('rolesPanel.viewMembers') }}</Button>
                                </Link>
                                <Button
                                    v-if="canDeleteSelectedRole"
                                    variant="secondary"
                                    class="!text-red-600 dark:!text-red-400"
                                    @click="destroyRole"
                                >
                                    {{ t('common.delete') }}
                                </Button>
                                <Button
                                    v-if="canUpdate"
                                    :disabled="form.processing || !isDirty"
                                    @click="saveRole"
                                >
                                    {{ t('rolesPanel.savePermissions') }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="border-b border-line px-3 py-2.5">
                        <h2 class="text-sm font-semibold text-ink">{{ t('rolesPanel.selectRole') }}</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ t('rolesPanel.selectRoleHint') }}</p>
                    </div>

                    <EmptyState
                        v-if="!selectedRoleId"
                        class="flex-1"
                        :title="t('rolesPanel.selectRole')"
                        :description="t('rolesPanel.selectRoleHint')"
                    />

                    <RolePermissionsPanel
                        v-else
                        v-model="form.permissions"
                        :permissions="permissions"
                        :disabled="!canUpdate"
                    />

                    <p v-if="selectedRoleId && form.errors.permissions" class="border-t border-line px-3 py-2 text-sm text-danger">
                        {{ form.errors.permissions }}
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
