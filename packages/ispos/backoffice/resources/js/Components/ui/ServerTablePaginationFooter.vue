<script setup lang="ts" generic="T">
import TablePagination from '@/Components/ui/TablePagination.vue';
import { useServerTablePagination } from '@/Composables/useServerTablePagination';
import type { Paginated } from '@/types';
import { type MaybeRefOrGetter, toValue } from 'vue';

const props = defineProps<{
    paginated: Paginated<T>;
    routeName: string;
    query?: MaybeRefOrGetter<Record<string, string | number | undefined>>;
}>();

const { page, pageSize, total, setPage, setPageSize } = useServerTablePagination(
    () => props.paginated,
    props.routeName,
    () => toValue(props.query) ?? {},
);
</script>

<template>
    <TablePagination
        :total="total"
        :page="page"
        :page-size="pageSize"
        @update:page="setPage"
        @update:page-size="setPageSize"
    />
</template>
