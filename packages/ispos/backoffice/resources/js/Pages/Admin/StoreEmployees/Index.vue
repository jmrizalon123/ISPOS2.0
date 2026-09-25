<script setup lang="ts">
import AssignUsersModal from '@/Components/admin/AssignUsersModal.vue';
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
    stores: Array<{
        id: string;
        store_name: string;
        store_code: string;
        company_id: string;
        members_count: number;
    }>;
    selectedStoreId: string | null;
    assignments: Array<{
        user_id: string;
        store_id: string;
        user_name: string;
        email: string;
        status: string;
        roles: string[];
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
        roles: string[];
    }>;
    lockStoreSelection?: boolean;
    filters: {
        search: string;
        store_id: string;
    };
}>();

const showStorePanel = computed(() => !props.lockStoreSelection && props.stores.length > 1);

const storeSearch = ref('');
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

const selectedStore = computed(
    () => props.stores.find((store) => store.id === props.selectedStoreId) ?? null,
);

const filteredStores = computed(() => {
    const term = storeSearch.value.trim().toLowerCase();

    if (!term) {
        return props.stores;
    }

    return props.stores.filter(
        (store) =>
            store.store_name.toLowerCase().includes(term) ||
            store.store_code.toLowerCase().includes(term),
    );
});

const countLabel = computed(() => {
    const count = props.assignments.length;
    const word = count === 1 ? t('entities.storeEmployee') : t('entities.storeEmployees');

    return `${count.toLocaleString()} ${word}`;
});

watch(memberSearch, (nextSearch) => {
    if (!props.selectedStoreId) {
        return;
    }

    currentPage.value = 1;

    router.get(
        route('admin.users.store-employees.index'),
        {
            store_id: props.selectedStoreId,
            search: nextSearch || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
});

const paginationFrom = computed(() =>
    props.assignments.length ? (currentPage.value - 1) * pageSize.value + 1 : null,
);

function sortValue(row: (typeof props.assignments)[number], key: string): string | number {
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

const sortedAssignments = computed(() => {
    if (!sortKey.value) {
        return props.assignments;
    }

    const direction = sortDirection.value === 'asc' ? 1 : -1;
    const key = sortKey.value;

    return [...props.assignments].sort((left, right) => {
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

const paginatedAssignments = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;

    return sortedAssignments.value.slice(start, start + pageSize.value);
});

watch([sortKey, sortDirection], () => {
    currentPage.value = 1;
});

watch(
    () => [props.selectedStoreId, props.assignments.length, pageSize.value] as const,
    () => {
        const maxPage = Math.max(1, Math.ceil(props.assignments.length / pageSize.value));

        if (currentPage.value > maxPage) {
            currentPage.value = maxPage;
        }
    },
);

function selectStore(storeId: string) {
    currentPage.value = 1;

    router.get(
        route('admin.users.store-employees.index'),
        {
            store_id: storeId,
            search: memberSearch.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openStoreAccess(row: (typeof props.assignments)[number]) {
    if (!row.stores.length) {
        return;
    }

    storeAccessModal.value = {
        userName: row.user_name,
        stores: row.stores,
    };
}

async function removeAssignment(userId: string, userName: string) {
    if (!props.selectedStoreId) {
        return;
    }

    const confirmed = await confirmAction({
        title: t('messages.confirmRemoveStoreAssignment.title'),
        message: t('messages.confirmRemoveStoreAssignment.message', { name: userName }),
        confirmLabel: t('common.remove'),
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(route('admin.users.store-employees.destroy', [userId, props.selectedStoreId]), {
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
    { key: 'roles', label: t('columns.roles'), sortable: false, minWidth: 100 },
    { key: 'status', label: t('common.status'), minWidth: 96 },
    ...auditCols.value,
]);
</script>

<template>
    <Head :title="t('pages.storeEmployees.title')" />
    <AppLayout>
        <template #header>{{ t('pages.users.title') }}</template>
        <template #subheader>{{ t('pages.storeEmployees.title') }}</template>

        <div class="index-page index-page--fill flex flex-col gap-2">
            <IndexPageHeader
                class="shrink-0"
                :back-href="route('admin.users.index')"
                :back-label="t('common.back')"
                :eyebrow="t('pages.storeEmployees.eyebrow')"
                :title="t('pages.storeEmployees.title')"
                :description="t('pages.storeEmployees.description')"
            />

            <div
                class="grid min-h-0 flex-1 gap-3"
                :class="showStorePanel ? 'lg:grid-cols-[280px_minmax(0,1fr)]' : ''"
            >
                <aside v-if="showStorePanel" class="ui-panel flex flex-col overflow-hidden">
                    <div class="border-b border-line px-3 py-2">
                        <h2 class="text-sm font-semibold text-ink">{{ t('storeEmployees.storesPanel') }}</h2>
                        <Input
                            v-model="storeSearch"
                            class="mt-1.5"
                            :placeholder="t('pages.stores.searchPlaceholder')"
                        />
                    </div>
                    <div class="flex-1 overflow-y-auto p-2">
                        <button
                            v-for="store in filteredStores"
                            :key="store.id"
                            type="button"
                            class="mb-1 flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm transition"
                            :class="
                                store.id === selectedStoreId
                                    ? 'bg-accent text-white shadow-sm dark:text-slate-950'
                                    : 'text-ink hover:bg-surface-muted'
                            "
                            @click="selectStore(store.id)"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ store.store_name }}</span>
                                <span
                                    class="block truncate text-xs"
                                    :class="store.id === selectedStoreId ? 'text-white/80 dark:text-slate-700' : 'text-ink-muted'"
                                >
                                    {{ store.store_code }}
                                </span>
                            </span>
                            <Badge
                                :variant="store.id === selectedStoreId ? 'neutral' : 'accent'"
                                class="shrink-0 tabular-nums"
                            >
                                {{ store.members_count }}
                            </Badge>
                        </button>
                        <p v-if="!filteredStores.length" class="px-3 py-6 text-center text-sm text-ink-muted">
                            {{ t('pages.stores.emptyTitle') }}
                        </p>
                    </div>
                </aside>

                <section class="ui-panel flex flex-col overflow-hidden">
                    <div v-if="selectedStoreId" class="shrink-0 border-b border-line">
                        <div class="flex items-start justify-between gap-3 px-3 py-2.5">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-ink">
                                    {{ selectedStore?.store_name }}
                                </h2>
                                <p v-if="selectedStore?.store_code" class="mt-0.5 truncate text-xs text-ink-muted">
                                    {{ selectedStore.store_code }}
                                </p>
                            </div>
                            <Button class="shrink-0" @click="showAssignModal = true">
                                {{ t('pages.storeEmployees.newButton') }}
                            </Button>
                        </div>
                        <div class="border-t border-line px-3 py-2">
                            <IndexToolbar>
                                <Input
                                    v-model="memberSearch"
                                    :placeholder="t('pages.storeEmployees.searchPlaceholder')"
                                    class="!w-full sm:!w-56"
                                />
                                <template #actions>
                                    <Badge variant="neutral" class="tabular-nums">{{ countLabel }}</Badge>
                                </template>
                            </IndexToolbar>
                        </div>
                    </div>

                    <div v-else class="border-b border-line px-3 py-2.5">
                        <h2 class="text-sm font-semibold text-ink">{{ t('storeEmployees.selectStore') }}</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ t('storeEmployees.selectStoreHint') }}</p>
                    </div>

                    <EmptyState
                        v-if="!selectedStoreId"
                        class="flex-1"
                        :title="t('storeEmployees.selectStore')"
                        :description="t('storeEmployees.selectStoreHint')"
                    />

                    <EmptyState
                        v-else-if="!assignments.length"
                        class="flex-1"
                        :title="t('pages.storeEmployees.emptyTitle')"
                        :description="t('pages.storeEmployees.emptyDescription')"
                        :action-label="t('pages.storeEmployees.newButton')"
                        @action="showAssignModal = true"
                    />

                    <DataTable
                        v-else
                        class="flex-1"
                        :columns="columns"
                        :rows="paginatedAssignments"
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
                        <template #cell-roles="{ row }">
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in row.roles.slice(0, 1)"
                                    :key="role"
                                    variant="accent"
                                    :title="role"
                                >
                                    {{ role }}
                                </Badge>
                                <Badge v-if="row.roles.length > 1" variant="neutral" :title="row.roles.slice(1).join(', ')">
                                    +{{ row.roles.length - 1 }}
                                </Badge>
                            </div>
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
                                @click="removeAssignment(row.user_id, row.user_name)"
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
                                :total="assignments.length"
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

        <AssignUsersModal
            :show="showAssignModal"
            :store-id="selectedStoreId ?? ''"
            :store-name="selectedStore?.store_name ?? ''"
            :users="availableUsers"
            @close="showAssignModal = false"
        />

        <StoreAccessModal
            :show="!!storeAccessModal"
            :user-name="storeAccessModal?.userName ?? ''"
            :stores="storeAccessModal?.stores ?? []"
            :highlight-store-id="selectedStoreId"
            @close="storeAccessModal = null"
        />
    </AppLayout>
</template>
