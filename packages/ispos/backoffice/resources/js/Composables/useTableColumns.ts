import { lineTableColumn, paginatedRowNumber } from '@/config/tableColumns';
import type { DataTableColumn } from '@/Components/ui/DataTable.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export { lineTableColumn, paginatedRowNumber };

export function useAuditTableColumns() {
    const { t } = useI18n();

    return computed(() => [
        {
            key: 'creator',
            label: t('table.creator'),
            class: 'whitespace-nowrap',
            minWidth: 140,
        },
        {
            key: 'last_modifier',
            label: t('table.lastModifier'),
            class: 'whitespace-nowrap',
            minWidth: 140,
        },
    ] as const);
}

export function useCommonTableColumns(keys: Array<'code' | 'name' | 'parent' | 'company' | 'store' | 'status'>) {
    const { t } = useI18n();

    return computed(() =>
        keys.map((key) => ({
            key: key === 'code' ? 'category_code' : key,
            label: t(`common.${key}`),
            minWidth: 120,
        })),
    );
}

export function useLineTableColumn() {
    const { t } = useI18n();

    return computed(() => ({
        ...lineTableColumn,
        label: t('table.line'),
        sticky: 'start' as const,
        sortable: false,
        resizable: false,
        movable: false,
        minWidth: 52,
    }));
}

type ColumnDef = { key: string; label: string; class?: string; minWidth?: number };

export function useColumns(
    defs: Array<{ key: string; col?: string; class?: string; minWidth?: number }>,
) {
    const { t } = useI18n();

    return computed<ColumnDef[]>(() =>
        defs.map(({ key, col, class: colClass, minWidth }) => ({
            key,
            label: t(`columns.${col ?? key}`),
            minWidth: minWidth ?? 120,
            ...(colClass ? { class: colClass } : {}),
        })),
    );
}

export function withTableColumnDefaults(columns: DataTableColumn[]): DataTableColumn[] {
    return columns.map((column) => {
        if (column.key === 'line' || column.sticky === 'start') {
            return {
                sortable: false,
                resizable: false,
                movable: false,
                minWidth: 52,
                sticky: 'start' as const,
                ...column,
            };
        }

        return {
            minWidth: 120,
            ...column,
        };
    });
}
