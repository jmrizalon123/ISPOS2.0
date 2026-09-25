<script setup lang="ts" generic="TRow extends object = Record<string, unknown>">
import ServerTablePaginationFooter from '@/Components/ui/ServerTablePaginationFooter.vue';
import type { Paginated } from '@/types';
import { computed, onBeforeUnmount, ref, useSlots, watch, type MaybeRefOrGetter } from 'vue';

export type DataTableColumn = {
    key: string;
    label: string;
    class?: string;
    sticky?: 'start' | 'end';
    sortable?: boolean;
    resizable?: boolean;
    movable?: boolean;
    minWidth?: number;
};

const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: TRow[];
        stickyActions?: boolean;
        stickyHeader?: boolean;
        viewportFit?: boolean;
        compact?: boolean;
        dense?: boolean;
        flushTop?: boolean;
        interactiveColumns?: boolean;
        externalSort?: boolean;
        sortKey?: string | null;
        sortDirection?: 'asc' | 'desc';
        paginated?: Paginated<TRow>;
        paginationRoute?: string;
        paginationQuery?: MaybeRefOrGetter<Record<string, string | number | undefined>>;
    }>(),
    {
        stickyActions: false,
        stickyHeader: false,
        viewportFit: false,
        compact: false,
        dense: false,
        flushTop: false,
        interactiveColumns: false,
        externalSort: false,
        sortKey: null,
        sortDirection: 'asc',
        paginated: undefined,
        paginationRoute: undefined,
        paginationQuery: undefined,
    },
);

const slots = useSlots();

const STICKY_ACTIONS_WIDTH = 48;

const hasActionsColumn = computed(() => props.stickyActions && !!slots.actions);

const showFooter = computed(
    () => !!slots.footer || (!!props.paginated && !!props.paginationRoute),
);

const emit = defineEmits<{
    'update:sortKey': [string | null];
    'update:sortDirection': ['asc' | 'desc'];
}>();

const columnOrder = ref<string[]>([]);
const columnWidths = ref<Record<string, number>>({});
const internalSortKey = ref<string | null>(null);
const internalSortDirection = ref<'asc' | 'desc'>('asc');
const dragKey = ref<string | null>(null);
const dragOverKey = ref<string | null>(null);

const effectiveInteractiveColumns = computed(() => props.interactiveColumns || props.viewportFit);

const activeSortKey = computed(() =>
    props.externalSort ? props.sortKey : (props.sortKey ?? internalSortKey.value),
);

const activeSortDirection = computed(() =>
    props.externalSort ? props.sortDirection : internalSortDirection.value,
);

let resizingKey: string | null = null;
let resizeStartX = 0;
let resizeStartWidth = 0;

watch(
    () => props.columns.map((column) => column.key),
    (keys) => {
        const preserved = columnOrder.value.filter((key) => keys.includes(key));
        const appended = keys.filter((key) => !preserved.includes(key));

        columnOrder.value = [...preserved, ...appended];
    },
    { immediate: true },
);

const orderedColumns = computed(() => {
    const byKey = new Map(props.columns.map((column) => [column.key, column]));
    const stickyStart = columnOrder.value
        .filter((key) => byKey.get(key)?.sticky === 'start')
        .map((key) => byKey.get(key)!);
    const stickyEnd = columnOrder.value
        .filter((key) => byKey.get(key)?.sticky === 'end')
        .map((key) => byKey.get(key)!);
    const middle = columnOrder.value
        .filter((key) => {
            const column = byKey.get(key);

            return column != null && column.sticky !== 'start' && column.sticky !== 'end';
        })
        .map((key) => byKey.get(key)!);

    return [...stickyStart, ...middle, ...stickyEnd];
});

const hasPinnedColumns = computed(
    () => props.stickyActions || props.columns.some((column) => column.sticky != null),
);

const leadingColumns = computed(() =>
    orderedColumns.value.filter((column) => column.sticky !== 'end'),
);

const trailingColumns = computed(() =>
    orderedColumns.value.filter((column) => column.sticky === 'end'),
);

const hasTrailingChrome = computed(
    () => trailingColumns.value.length > 0 || !!slots.actions,
);

const usesStickyLayout = computed(
    () => props.viewportFit || props.stickyHeader || hasPinnedColumns.value,
);

const usesStickyHeader = computed(() => props.viewportFit || props.stickyHeader);

/** Spacer only when something is pinned after content — otherwise it leaves a blank strip on the right. */
const useSpacerColumn = computed(
    () =>
        hasTrailingChrome.value &&
        (props.viewportFit ||
            effectiveInteractiveColumns.value ||
            orderedColumns.value.some((column) => column.minWidth != null)),
);

const tableMinWidth = computed(() => {
    if (!useSpacerColumn.value) {
        return undefined;
    }

    const columnsWidth = orderedColumns.value.reduce(
        (total, column) =>
            total + (columnWidths.value[column.key] ?? column.minWidth ?? (effectiveInteractiveColumns.value ? 100 : 0)),
        0,
    );
    const actionsWidth = props.stickyActions ? 48 : 0;

    if (columnsWidth + actionsWidth === 0) {
        return undefined;
    }

    return `${columnsWidth + actionsWidth}px`;
});

function rowSortValue(row: TRow, key: string): string | number {
    const value = (row as Record<string, unknown>)[key];

    if (value == null) {
        return '';
    }

    if (typeof value === 'number') {
        return value;
    }

    if (typeof value === 'boolean') {
        return value ? 1 : 0;
    }

    if (typeof value === 'object') {
        return JSON.stringify(value).toLowerCase();
    }

    return String(value).toLowerCase();
}

const displayedRows = computed(() => {
    if (props.externalSort || !effectiveInteractiveColumns.value || !activeSortKey.value) {
        return props.rows;
    }

    const direction = activeSortDirection.value === 'asc' ? 1 : -1;
    const key = activeSortKey.value;

    return [...props.rows].sort((left, right) => {
        const leftValue = rowSortValue(left, key);
        const rightValue = rowSortValue(right, key);

        if (leftValue < rightValue) {
            return -1 * direction;
        }

        if (leftValue > rightValue) {
            return 1 * direction;
        }

        return 0;
    });
});

function cellPadding(compact: boolean, dense: boolean): string {
    if (dense) {
        return 'px-2 py-0.5';
    }

    if (compact) {
        return 'px-2 py-1.5';
    }

    return 'px-4 py-3.5';
}

function headerPadding(compact: boolean, dense: boolean): string {
    if (dense) {
        return 'px-2 py-1';
    }

    if (compact) {
        return 'px-2 py-2';
    }

    return 'px-4 py-3.5';
}

const cellClass = 'whitespace-nowrap bg-surface text-ink group-hover:bg-surface-muted';
const headerClass = 'text-left text-[11px] font-semibold uppercase tracking-wider text-ink-muted';

function cellValue(row: TRow, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}

function rowKey(row: TRow, index: number): string {
    const id = (row as Record<string, unknown>).id;
    return id != null ? String(id) : String(index);
}

function stickyColumnClass(column: DataTableColumn, section: 'header' | 'body'): string {
    const classes: string[] = [];
    const isHeader = section === 'header';

    if (column.sticky === 'start') {
        classes.push('data-table__col-sticky-start');
    } else if (column.sticky === 'end') {
        classes.push('data-table__col-sticky-end');
    }

    if (usesStickyHeader.value && isHeader) {
        classes.push('data-table__col-sticky-top');

        if (column.sticky === 'start') {
            classes.push('data-table__col-sticky-corner-start');
        } else if (column.sticky === 'end') {
            classes.push('data-table__col-sticky-corner-end');
        }
    }

    if (column.sticky === 'start' || column.sticky === 'end') {
        classes.push(isHeader ? 'bg-surface-muted' : 'bg-surface group-hover:bg-surface-muted');
    } else if (usesStickyHeader.value && isHeader) {
        classes.push('bg-surface-muted');
    }

    return classes.join(' ');
}

function stickyActionsClass(section: 'header' | 'body'): string {
    const classes = ['data-table__col-sticky-end', 'data-table__col-sticky-actions'];

    if (section === 'header') {
        classes.push('bg-surface-muted');

        if (usesStickyHeader.value) {
            classes.push('data-table__col-sticky-top', 'data-table__col-sticky-corner-end');
        }
    } else {
        classes.push('bg-surface group-hover:bg-surface-muted');
    }

    return classes.join(' ');
}

function columnAllowsSort(column: DataTableColumn): boolean {
    return effectiveInteractiveColumns.value && column.sortable !== false;
}

function columnAllowsResize(column: DataTableColumn): boolean {
    return effectiveInteractiveColumns.value && column.resizable !== false;
}

function columnAllowsMove(column: DataTableColumn): boolean {
    return effectiveInteractiveColumns.value && column.movable !== false && !column.sticky;
}

function columnWidthStyle(key: string): Record<string, string> | undefined {
    const column = props.columns.find((item) => item.key === key);
    const width = effectiveInteractiveColumns.value
        ? (columnWidths.value[key] ?? column?.minWidth)
        : column?.minWidth;

    if (!width) {
        if (effectiveInteractiveColumns.value) {
            return { minWidth: '100px' };
        }

        return undefined;
    }

    if (column?.sticky === 'start' || column?.sticky === 'end') {
        return {
            width: `${width}px`,
            minWidth: `${width}px`,
            maxWidth: `${width}px`,
        };
    }

    if (!effectiveInteractiveColumns.value) {
        return undefined;
    }

    return {
        minWidth: `${width}px`,
    };
}

function stickyEndColumnWidth(column: DataTableColumn): number {
    return columnWidths.value[column.key] ?? column.minWidth ?? 120;
}

function stickyEndRightOffset(columnKey: string): number {
    const endColumns = orderedColumns.value.filter((column) => column.sticky === 'end');
    const index = endColumns.findIndex((column) => column.key === columnKey);

    if (index === -1) {
        return 0;
    }

    let offset = hasActionsColumn.value ? STICKY_ACTIONS_WIDTH : 0;

    for (let i = index + 1; i < endColumns.length; i++) {
        offset += stickyEndColumnWidth(endColumns[i]);
    }

    return offset;
}

function pinnedCellStyle(column: DataTableColumn, section: 'header' | 'body'): Record<string, string> {
    const styles: Record<string, string> = {};

    if (column.sticky === 'start') {
        styles.position = 'sticky';
        styles.left = '0px';
        styles.zIndex = section === 'header' ? '50' : '20';
    } else if (column.sticky === 'end') {
        styles.position = 'sticky';
        styles.right = `${stickyEndRightOffset(column.key)}px`;
        styles.zIndex = section === 'header' ? '50' : '20';
    } else if (section === 'header' && usesStickyHeader.value) {
        styles.position = 'sticky';
        styles.top = '0px';
        styles.zIndex = '20';
    }

    if (section === 'header' && usesStickyHeader.value && column.sticky != null) {
        styles.top = '0px';
        styles.zIndex = '50';
    }

    return styles;
}

function stickyActionsCellStyle(section: 'header' | 'body'): Record<string, string> | undefined {
    if (!props.stickyActions) {
        return undefined;
    }

    return {
        position: 'sticky',
        right: '0px',
        minWidth: '48px',
        width: 'auto',
        zIndex: section === 'header' ? '50' : '20',
        ...(section === 'header' && usesStickyHeader.value ? { top: '0px' } : {}),
    };
}

function cellStyle(column: DataTableColumn, section: 'header' | 'body'): Record<string, string> {
    return {
        ...columnWidthStyle(column.key),
        ...pinnedCellStyle(column, section),
    };
}

function toggleSort(column: DataTableColumn) {
    if (!columnAllowsSort(column)) {
        return;
    }

    let nextKey: string | null;
    let nextDirection: 'asc' | 'desc';

    if (activeSortKey.value !== column.key) {
        nextKey = column.key;
        nextDirection = 'asc';
    } else if (activeSortDirection.value === 'asc') {
        nextKey = column.key;
        nextDirection = 'desc';
    } else {
        nextKey = null;
        nextDirection = 'asc';
    }

    if (props.externalSort) {
        emit('update:sortKey', nextKey);
        emit('update:sortDirection', nextDirection);
        return;
    }

    internalSortKey.value = nextKey;
    internalSortDirection.value = nextDirection;
}

function sortIndicator(column: DataTableColumn): string | null {
    if (activeSortKey.value !== column.key) {
        return null;
    }

    return activeSortDirection.value === 'asc' ? '↑' : '↓';
}

function onResizeMove(event: MouseEvent) {
    if (!resizingKey) {
        return;
    }

    const column = props.columns.find((item) => item.key === resizingKey);
    const minWidth = column?.minWidth ?? 56;
    const width = Math.max(minWidth, resizeStartWidth + event.clientX - resizeStartX);

    columnWidths.value = {
        ...columnWidths.value,
        [resizingKey]: width,
    };
}

function stopResize() {
    resizingKey = null;
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', stopResize);
    document.body.style.removeProperty('cursor');
    document.body.style.removeProperty('user-select');
}

function startResize(event: MouseEvent, key: string) {
    event.preventDefault();
    event.stopPropagation();

    const header = (event.target as HTMLElement).closest('th');

    resizingKey = key;
    resizeStartX = event.clientX;
    resizeStartWidth = header?.getBoundingClientRect().width ?? 120;

    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    document.addEventListener('mousemove', onResizeMove);
    document.addEventListener('mouseup', stopResize);
}

function onDragStart(event: DragEvent, column: DataTableColumn) {
    if (!columnAllowsMove(column)) {
        event.preventDefault();
        return;
    }

    dragKey.value = column.key;
    event.dataTransfer?.setData('text/plain', column.key);
    event.dataTransfer!.effectAllowed = 'move';
}

function onDragOver(event: DragEvent, column: DataTableColumn) {
    if (!columnAllowsMove(column) || !dragKey.value || dragKey.value === column.key) {
        return;
    }

    event.preventDefault();
    dragOverKey.value = column.key;
}

function onDragLeave(column: DataTableColumn) {
    if (dragOverKey.value === column.key) {
        dragOverKey.value = null;
    }
}

function onDrop(event: DragEvent, column: DataTableColumn) {
    event.preventDefault();

    const sourceKey = dragKey.value;

    dragKey.value = null;
    dragOverKey.value = null;

    if (!sourceKey || !columnAllowsMove(column) || sourceKey === column.key) {
        return;
    }

    const fromIndex = columnOrder.value.indexOf(sourceKey);
    const toIndex = columnOrder.value.indexOf(column.key);

    if (fromIndex < 0 || toIndex < 0) {
        return;
    }

    const next = [...columnOrder.value];
    next.splice(fromIndex, 1);
    next.splice(toIndex, 0, sourceKey);
    columnOrder.value = next;
}

function onDragEnd() {
    dragKey.value = null;
    dragOverKey.value = null;
}

onBeforeUnmount(() => {
    stopResize();
});
</script>

<template>
    <div
        class="data-table overflow-hidden border border-line bg-surface"
        :class="[
            flushTop ? 'rounded-b-xl rounded-t-none border-t-0 shadow-none' : 'rounded-xl shadow-soft',
            viewportFit ? 'data-table--viewport-fit min-h-0 flex-1' : undefined,
            dense ? 'data-table--dense' : compact ? 'data-table--compact' : undefined,
            effectiveInteractiveColumns ? 'data-table--interactive' : undefined,
            usesStickyLayout ? 'data-table--sticky' : undefined,
            usesStickyHeader ? 'data-table--sticky-header' : undefined,
        ]"
    >
        <div
            class="data-table__scroll scrollbar-visible overflow-x-auto"
            :class="viewportFit ? 'data-table-scroll--viewport-fit' : undefined"
        >
            <table
                class="w-full min-w-max"
                :style="tableMinWidth ? { minWidth: tableMinWidth } : undefined"
                :class="[
                    dense ? 'text-xs' : 'text-sm',
                    effectiveInteractiveColumns ? 'table-auto' : undefined,
                    usesStickyLayout ? 'border-separate border-spacing-0' : undefined,
                ]"
            >
                <thead>
                    <tr>
                        <th
                            v-for="column in leadingColumns"
                            :key="column.key"
                            :style="cellStyle(column, 'header')"
                            :class="[
                                headerClass,
                                headerPadding(compact, dense),
                                column.class,
                                stickyColumnClass(column, 'header'),
                                columnAllowsSort(column) ? 'data-table__header--sortable' : undefined,
                                columnAllowsMove(column) ? 'data-table__header--movable' : undefined,
                                dragOverKey === column.key ? 'data-table__header--drag-over' : undefined,
                                dragKey === column.key ? 'data-table__header--dragging' : undefined,
                            ]"
                            :draggable="columnAllowsMove(column)"
                            @click="toggleSort(column)"
                            @dragstart="onDragStart($event, column)"
                            @dragover="onDragOver($event, column)"
                            @dragleave="onDragLeave(column)"
                            @drop="onDrop($event, column)"
                            @dragend="onDragEnd"
                        >
                            <span class="data-table__header-content">
                                <slot :name="`header-${column.key}`" :column="column">
                                    <span>{{ column.label }}</span>
                                </slot>
                                <span v-if="sortIndicator(column)" class="data-table__sort-indicator">
                                    {{ sortIndicator(column) }}
                                </span>
                            </span>
                            <span
                                v-if="columnAllowsResize(column)"
                                class="data-table__resize-handle"
                                @mousedown="startResize($event, column.key)"
                                @click.stop
                            />
                        </th>
                        <th
                            v-if="useSpacerColumn"
                            aria-hidden="true"
                            class="data-table__spacer bg-surface-muted"
                        />
                        <th
                            v-for="column in trailingColumns"
                            :key="column.key"
                            :style="cellStyle(column, 'header')"
                            :class="[
                                headerClass,
                                headerPadding(compact, dense),
                                column.class,
                                stickyColumnClass(column, 'header'),
                                columnAllowsSort(column) ? 'data-table__header--sortable' : undefined,
                                columnAllowsMove(column) ? 'data-table__header--movable' : undefined,
                                dragOverKey === column.key ? 'data-table__header--drag-over' : undefined,
                                dragKey === column.key ? 'data-table__header--dragging' : undefined,
                            ]"
                            :draggable="columnAllowsMove(column)"
                            @click="toggleSort(column)"
                            @dragstart="onDragStart($event, column)"
                            @dragover="onDragOver($event, column)"
                            @dragleave="onDragLeave(column)"
                            @drop="onDrop($event, column)"
                            @dragend="onDragEnd"
                        >
                            <span class="data-table__header-content">
                                <slot :name="`header-${column.key}`" :column="column">
                                    <span>{{ column.label }}</span>
                                </slot>
                                <span v-if="sortIndicator(column)" class="data-table__sort-indicator">
                                    {{ sortIndicator(column) }}
                                </span>
                            </span>
                            <span
                                v-if="columnAllowsResize(column)"
                                class="data-table__resize-handle"
                                @mousedown="startResize($event, column.key)"
                                @click.stop
                            />
                        </th>
                        <th
                            v-if="$slots.actions"
                            scope="col"
                            :style="stickyActionsCellStyle('header')"
                            :class="[
                                headerClass,
                                headerPadding(compact, dense),
                                'w-auto min-w-12 whitespace-nowrap',
                                stickyActions ? stickyActionsClass('header') : undefined,
                            ]"
                        />
                    </tr>
                </thead>
                <tbody class="bg-surface">
                    <tr
                        v-for="(row, index) in displayedRows"
                        :key="rowKey(row, index)"
                        class="group"
                    >
                        <td
                            v-for="column in leadingColumns"
                            :key="column.key"
                            :style="cellStyle(column, 'body')"
                            :class="[
                                cellClass,
                                cellPadding(compact, dense),
                                column.class,
                                stickyColumnClass(column, 'body'),
                            ]"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :index="index">
                                {{ cellValue(row, column.key) }}
                            </slot>
                        </td>
                        <td
                            v-if="useSpacerColumn"
                            aria-hidden="true"
                            class="data-table__spacer bg-surface group-hover:bg-surface-muted"
                        />
                        <td
                            v-for="column in trailingColumns"
                            :key="column.key"
                            :style="cellStyle(column, 'body')"
                            :class="[
                                cellClass,
                                cellPadding(compact, dense),
                                column.class,
                                stickyColumnClass(column, 'body'),
                            ]"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :index="index">
                                {{ cellValue(row, column.key) }}
                            </slot>
                        </td>
                        <td
                            v-if="$slots.actions"
                            :style="stickyActionsCellStyle('body')"
                            :class="[
                                'w-auto min-w-12 whitespace-nowrap text-right',
                                cellPadding(compact, dense),
                                stickyActions ? stickyActionsClass('body') : undefined,
                            ]"
                        >
                            <slot name="actions" :row="row" :index="index" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div
            v-if="showFooter"
            class="shrink-0 border-t border-line bg-surface-muted"
            :class="dense ? 'px-2 py-1' : compact ? 'px-2 py-1.5' : 'px-4 py-3'"
        >
            <slot name="footer">
                <ServerTablePaginationFooter
                    v-if="paginated && paginationRoute"
                    :paginated="paginated"
                    :route-name="paginationRoute"
                    :query="paginationQuery"
                />
            </slot>
        </div>
    </div>
</template>
