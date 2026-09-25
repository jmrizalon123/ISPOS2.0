export const lineTableColumn = {
    key: 'line',
    label: '#',
    class: 'w-10 text-center',
    sticky: 'start' as const,
};

export function paginatedRowNumber(from: number | null | undefined, index: number): number {
    return from != null ? from + index : index + 1;
}

export const auditTableColumns = [
    { key: 'creator', label: 'Creator', class: 'whitespace-normal min-w-[10rem]' },
    { key: 'last_modifier', label: 'Last modifier', class: 'whitespace-normal min-w-[10rem]' },
] as const;
