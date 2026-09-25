<script setup lang="ts">
import AssignUsersToRoleModal from '@/Components/admin/AssignUsersToRoleModal.vue';
import StoreAccessModal from '@/Components/admin/StoreAccessModal.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import TablePagination from '@/Components/ui/TablePagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useConfirm';
import { paginatedRowNumber, useAuditTableColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import TableAuditStamp from '@/Components/ui/TableAuditStamp.vue';
import type { AuditableUser } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const auditCols = useAuditTableColumns();
const lineCol = useLineTableColumn();

const props = defineProps<{
    roles: Array<{
        id: number;
        name: string;
        users_count: number;
        permissions_count: number;
    }>;
    selectedRoleId: number | null;
    members: Array<{
        user_id: string;
        role_id: number;
        user_name: string;
        email: string;
        status: string;
        creator?: AuditableUser | null;
        updater?: AuditableUser | null;
        created_at?: string | null;
        updated_at?: string | null;
        stores: Array<{ id: string; store_name: string; store_code: string }>;
    }>;
    availableUsers: Array<{
        id: string;
        name: string;
        email: string;
        status: string;
    }>;
    filters: {
        search: string;
        role_id: string;
    };
}>();

const roleSearch = ref('');
const memberSearch = ref(props.filters.search ?? '');
const currentPage = ref(1);
const pageSize = ref(10);
const sortKey = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');
const showAssignModal = ref(false);
const storeAccessModal = ref<{
    userName: string;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
} | null>(null);

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

const countLabel = computed(() => {
    const count = props.members.length;
    const word = count === 1 ? t('entities.user') : t('common.users');

    return `${count.toLocaleString()} ${word}`;
});

watch(memberSearch, (nextSearch) => {
    if (!props.selectedRoleId) {
        return;
    }

    currentPage.value = 1;

    router.get(
        route('admin.users.role-members.index'),
        {
            role_id: props.selectedRoleId,
            search: nextSearch || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
});

const paginationFrom = computed(() =>
    props.members.length ? (currentPage.value - 1) * pageSize.value + 1 : null,
);

function sortValue(row: (typeof props.members)[number], key: string): string | number {
    switch (key) {
        case 'user_name':
            return row.user_name.toLowerCase();
        case 'email':
            return row.email.toLowerCase();
        case 'status':
            return row.status.toLowerCase();
        case 'creator':
            return row.creator?.name?.toLowerCase() ?? '';
        case 'last_modifier':
            return row.updater?.name?.toLowerCase() ?? '';
        default:
            return '';
    }
}

const sortedMembers = computed(() => {
    if (!sortKey.value) {
        return props.members;
    }

    const direction = sortDirection.value === 'asc' ? 1 : -1;
    const key = sortKey.value;

    return [...props.members].sort((left, right) => {
        const leftValue = sortValue(left, key);
        const rightValue = sortValue(right, key);

        if (leftValue < rightValue) {
            return -1 * direction;
        }

        if (leftValue > rightValue) {
            return 1 * direction;
        }

        return 0;
    });
});

const paginatedMembers = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;

    return sortedMembers.value.slice(start, start + pageSize.value);
});

watch([sortKey, sortDirection], () => {
    currentPage.value = 1;
});

watch(
    () => [props.selectedRoleId, props.members.length, pageSize.value] as const,
    () => {
        const maxPage = Math.max(1, Math.ceil(props.members.length / pageSize.value));

        if (currentPage.value > maxPage) {
            currentPage.value = maxPage;
        }
    },
);

function selectRole(roleId: number) {
    currentPage.value = 1;

    router.get(
        route('admin.users.role-members.index'),
        {
            role_id: roleId,
            search: memberSearch.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openStoreAccess(row: (typeof props.members)[number]) {
    if (!row.stores.length) {
        return;
    }

    storeAccessModal.value = {
        userName: row.user_name,
        stores: row.stores,
    };
}

async function removeMember(userId: string, userName: string) {
    if (!props.selectedRoleId) {
        return;
    }

    const confirmed = await confirmAction({
        title: t('messages.confirmRemoveRoleAssignment.title'),
        message: t('messages.confirmRemoveRoleAssignment.message', { name: userName }),
        confirmLabel: t('common.remove'),
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(route('admin.users.role-members.destroy', [userId, props.selectedRoleId]), {
        preserveScroll: true,
    });
}

const columns = computed(() => [
    lineCol.value,
    { key: 'user_name', label: t('common.name'), minWidth: 120 },
    { key: 'email', label: t('columns.email'), minWidth: 160 },
    {
        key: 'stores',
        label: t('common.store'),
        class: 'text-center',
        sortable: false,
        minWidth: 72,
    },
    { key: 'status', label: t('common.status'), minWidth: 96 },
    ...auditCols.value,
]);
</script>

<template>
    <Head :title="t('pages.roleMembers.title')" />
    <AppLayout>
        <template #header>{{ t('pages.users.title') }}</template>
        <template #subheader>{{ t('pages.roleMembers.title') }}</template>

        <div class="index-page index-page--fill flex flex-col gap-2">
            <IndexPageHeader
                class="shrink-0"
                :back-href="route('admin.users.index')"
                :back-label="t('common.back')"
                :eyebrow="t('pages.roleMembers.eyebrow')"
                :title="t('pages.roleMembers.title')"
                :description="t('pages.roleMembers.description')"
            />

            <div class="grid min-h-0 flex-1 gap-3 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="ui-panel flex flex-col overflow-hidden">
                    <div class="border-b border-line px-3 py-2">
                        <h2 class="text-sm font-semibold text-ink">{{ t('roleMembers.rolesPanel') }}</h2>
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
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-ink">
                                    {{ selectedRole?.name }}
                                </h2>
                                <p class="mt-0.5 truncate text-xs text-ink-muted">
                                    {{ t('roleMembers.permissionCount', { count: selectedRole?.permissions_count ?? 0 }) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <Link :href="route('admin.roles.index', { role_id: selectedRoleId })">
                                    <Button variant="secondary">{{ t('roleMembers.editPermissions') }}</Button>
                                </Link>
                                <Button @click="showAssignModal = true">
                                    {{ t('pages.roleMembers.newButton') }}
                                </Button>
                            </div>
                        </div>
                        <div class="border-t border-line px-3 py-2">
                            <IndexToolbar>
                                <Input
                                    v-model="memberSearch"
                                    :placeholder="t('pages.roleMembers.searchPlaceholder')"
                                    class="!w-full sm:!w-56"
                                />
                                <template #actions>
                                    <Badge variant="neutral" class="tabular-nums">{{ countLabel }}</Badge>
                                </template>
                            </IndexToolbar>
                        </div>
                    </div>

                    <div v-else class="border-b border-line px-3 py-2.5">
                        <h2 class="text-sm font-semibold text-ink">{{ t('roleMembers.selectRole') }}</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ t('roleMembers.selectRoleHint') }}</p>
                    </div>

                    <EmptyState
                        v-if="!selectedRoleId"
                        class="flex-1"
                        :title="t('roleMembers.selectRole')"
                        :description="t('roleMembers.selectRoleHint')"
                    />

                    <EmptyState
                        v-else-if="!members.length"
                        class="flex-1"
                        :title="t('pages.roleMembers.emptyTitle')"
                        :description="t('pages.roleMembers.emptyDescription')"
                        :action-label="t('pages.roleMembers.newButton')"
                        @action="showAssignModal = true"
                    />

                    <DataTable
                        v-else
                        class="flex-1"
                        :columns="columns"
                        :rows="paginatedMembers"
                        sticky-actions
                        viewport-fit
                        compact
                        flush-top
                        external-sort
                        :sort-key="sortKey"
                        :sort-direction="sortDirection"
                        @update:sort-key="sortKey = $event"
                        @update:sort-direction="sortDirection = $event"
                    >
                        <template #cell-line="{ index }">
                            <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(paginationFrom, index) }}</span>
                        </template>
                        <template #cell-user_name="{ row }">
                            <Link
                                :href="route('admin.users.edit', row.user_id)"
                                class="font-medium text-accent hover:underline"
                            >
                                {{ row.user_name }}
                            </Link>
                        </template>
                        <template #cell-stores="{ row }">
                            <button
                                v-if="row.stores.length"
                                type="button"
                                class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-accent transition hover:bg-accent/20"
                                :aria-label="t('storeEmployees.viewStoreAccess', { count: row.stores.length, name: row.user_name })"
                                @click="openStoreAccess(row)"
                            >
                                {{ row.stores.length }}
                            </button>
                            <span v-else class="text-ink-muted">—</span>
                        </template>
                        <template #cell-status="{ row }">
                            <Badge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</Badge>
                        </template>
                        <template #cell-creator="{ row }">
                            <TableAuditStamp compact :user="row.creator" :at="row.created_at" />
                        </template>
                        <template #cell-last_modifier="{ row }">
                            <TableAuditStamp compact :user="row.updater" :at="row.updated_at" />
                        </template>
                        <template #actions="{ row }">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md p-1.5 text-ink-muted transition hover:bg-surface-muted hover:text-red-600 dark:hover:text-red-400"
                                :aria-label="t('common.remove')"
                                @click="removeMember(row.user_id, row.user_name)"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </template>
                        <template #footer>
                            <TablePagination
                                :total="members.length"
                                :page="currentPage"
                                :page-size="pageSize"
                                @update:page="currentPage = $event"
                                @update:page-size="pageSize = $event"
                            />
                        </template>
                    </DataTable>
                </section>
            </div>
        </div>

        <AssignUsersToRoleModal
            :show="showAssignModal"
            :role-id="selectedRoleId"
            :role-name="selectedRole?.name ?? ''"
            :users="availableUsers"
            @close="showAssignModal = false"
        />

        <StoreAccessModal
            :show="!!storeAccessModal"
            :user-name="storeAccessModal?.userName ?? ''"
            :stores="storeAccessModal?.stores ?? []"
            @close="storeAccessModal = null"
        />
    </AppLayout>
</template>
