<script setup lang="ts">
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import AssignUserStatusCell from '@/Components/admin/AssignUserStatusCell.vue';
import { ensureAgGridRegistered } from '@/agGridSetup';
import { readCssRgb, useAgGridAppTheme } from '@/Composables/useAgGridAppTheme';
import { useForm } from '@inertiajs/vue3';
import { AgGridVue } from 'ag-grid-vue3';
import type {
    CellStyleFunc,
    ColDef,
    GridApi,
    GridReadyEvent,
    SelectionChangedEvent,
    ValueGetterParams,
} from 'ag-grid-community';
import { computed, nextTick, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';

ensureAgGridRegistered();

type AssignUserRow = {
    id: string;
    name: string;
    email: string;
    status: string;
};

const props = defineProps<{
    show: boolean;
    roleId: number | null;
    roleName: string;
    users: AssignUserRow[];
}>();

const emit = defineEmits<{ close: [] }>();

const { t } = useI18n();
const { gridTheme } = useAgGridAppTheme();
const search = ref('');
const gridApi = shallowRef<GridApi<AssignUserRow> | null>(null);

const form = useForm({
    role_id: props.roleId ?? 0,
    user_ids: [] as string[],
});

watch(
    () => props.show,
    (visible) => {
        if (!visible) {
            return;
        }

        form.clearErrors();
        form.role_id = props.roleId ?? 0;
        form.user_ids = [];
        search.value = '';
        gridApi.value?.deselectAll();
    },
);

watch(
    () => props.roleId,
    (roleId) => {
        form.role_id = roleId ?? 0;
    },
);

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.users;
    }

    return props.users.filter(
        (user) =>
            user.name.toLowerCase().includes(term) ||
            user.email.toLowerCase().includes(term),
    );
});

const nameCellStyle: CellStyleFunc<AssignUserRow> = (params) => {
    if (!params.data) {
        return undefined;
    }

    const selected = form.user_ids.includes(params.data.id);

    return {
        fontWeight: '600',
        color: selected ? readCssRgb('--color-accent', '#2563eb') : readCssRgb('--color-text', '#18181b'),
    };
};

const columnDefs = computed<ColDef<AssignUserRow>[]>(() => [
    {
        colId: 'line',
        headerName: '#',
        width: 52,
        maxWidth: 56,
        pinned: 'left',
        sortable: false,
        filter: false,
        suppressMovable: true,
        valueGetter: (params: ValueGetterParams<AssignUserRow>) => {
            if (!params.node || params.node.rowIndex == null) {
                return '';
            }

            const page = params.api.paginationGetCurrentPage();
            const pageSize = params.api.paginationGetPageSize();

            return page * pageSize + params.node.rowIndex + 1;
        },
        cellClass: 'tabular-nums',
        cellStyle: {
            color: readCssRgb('--color-text-muted', '#71717a'),
        },
    },
    {
        field: 'name',
        headerName: t('common.name'),
        flex: 1.2,
        minWidth: 140,
        cellStyle: nameCellStyle,
    },
    {
        field: 'email',
        headerName: t('columns.email'),
        flex: 1.4,
        minWidth: 180,
        cellStyle: {
            color: readCssRgb('--color-text-muted', '#71717a'),
        },
    },
    {
        field: 'status',
        headerName: t('common.status'),
        width: 108,
        sortable: false,
        headerClass: 'assign-users-grid-status-header',
        cellClass: 'assign-users-grid-status-cell',
        cellRenderer: AssignUserStatusCell,
    },
]);

const defaultColDef = computed<ColDef>(() => ({
    sortable: true,
    resizable: false,
    suppressHeaderMenuButton: true,
}));

const gridOptions = computed(() => ({
    rowSelection: {
        mode: 'multiRow' as const,
        checkboxes: true,
        headerCheckbox: true,
        selectAll: 'filtered' as const,
        enableClickSelection: true,
    },
    selectionColumnDef: {
        width: 48,
        maxWidth: 48,
        pinned: 'left' as const,
        suppressHeaderMenuButton: true,
        sortable: false,
    },
    pagination: true,
    paginationPageSize: 10,
    paginationPageSizeSelector: [10, 20, 50],
    suppressCellFocus: true,
    animateRows: false,
    getRowId: (params: { data: AssignUserRow }) => params.data.id,
    overlayNoRowsTemplate: `<span class="assign-users-grid-empty">${t('roleMembers.noUsersMatch')}</span>`,
}));

function syncGridSelection() {
    if (!gridApi.value) {
        return;
    }

    gridApi.value.deselectAll();

    gridApi.value.forEachNode((node) => {
        if (node.data && form.user_ids.includes(node.data.id)) {
            node.setSelected(true, false);
        }
    });
}

function refreshNameCells() {
    gridApi.value?.refreshCells({ columns: ['name'], force: true });
}

function onGridReady(event: GridReadyEvent<AssignUserRow>) {
    gridApi.value = event.api;
    syncGridSelection();
}

function onSelectionChanged(event: SelectionChangedEvent<AssignUserRow>) {
    const visibleIds = new Set(filteredUsers.value.map((user) => user.id));
    const selectedVisibleIds = event.api.getSelectedRows().map((row) => row.id);
    const preserved = form.user_ids.filter((id) => !visibleIds.has(id));

    form.user_ids = [...preserved, ...selectedVisibleIds];
    refreshNameCells();
}

watch(filteredUsers, () => {
    nextTick(() => syncGridSelection());
});

watch(
    () => form.user_ids.length,
    () => refreshNameCells(),
);

function submit() {
    form.post(route('admin.users.role-members.bulk-store'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <template #header>
            <div class="flex min-w-0 flex-1 flex-wrap items-start justify-between gap-3 pr-6">
                <div class="min-w-0">
                    <h3 class="font-display text-lg font-semibold text-ink">
                        {{ t('roleMembers.assignModalTitle') }}
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                        {{ t('roleMembers.assignModalDescription', { role: roleName }) }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Button variant="secondary" type="button" @click="emit('close')">{{ t('common.cancel') }}</Button>
                    <Button type="button" :disabled="form.processing || form.user_ids.length === 0" @click="submit">
                        {{ t('roleMembers.assignSelected', { count: form.user_ids.length }) }}
                    </Button>
                </div>
            </div>
        </template>

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="min-w-0 flex-1">
                <Input
                    v-model="search"
                    :placeholder="t('pages.roleMembers.searchPlaceholder')"
                />
            </div>
            <Badge variant="accent" class="shrink-0 tabular-nums">
                {{ t('roleMembers.selectedCount', { count: form.user_ids.length }) }}
            </Badge>
        </div>

        <div
            v-if="filteredUsers.length"
            class="assign-users-grid-shell -mx-6 -mb-6 overflow-hidden border-t border-line"
        >
            <AgGridVue
                class="assign-users-grid"
                style="width: 100%; height: 360px"
                :theme="gridTheme"
                :row-data="filteredUsers"
                :column-defs="columnDefs"
                :default-col-def="defaultColDef"
                v-bind="gridOptions"
                @grid-ready="onGridReady"
                @selection-changed="onSelectionChanged"
            />
            <p v-if="form.errors.user_ids" class="border-t border-line px-6 py-2 text-sm text-danger">
                {{ form.errors.user_ids }}
            </p>
        </div>

        <p v-else class="mt-3 rounded-xl border border-line bg-surface-muted/20 px-4 py-10 text-center text-sm text-ink-muted">
            {{ users.length ? t('roleMembers.noUsersMatch') : t('roleMembers.noAvailableUsers') }}
        </p>

        <p v-if="form.errors.user_ids && !filteredUsers.length" class="mt-2 text-sm text-danger">
            {{ form.errors.user_ids }}
        </p>
    </Modal>
</template>
