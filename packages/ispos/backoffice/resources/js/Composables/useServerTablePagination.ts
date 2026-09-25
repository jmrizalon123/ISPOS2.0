import { router } from '@inertiajs/vue3';
import type { Paginated } from '@/types';
import { computed, ref, watch, type MaybeRefOrGetter, toValue } from 'vue';

export function useServerTablePagination<T>(
    paginated: MaybeRefOrGetter<Paginated<T>>,
    routeName: string,
    query: MaybeRefOrGetter<Record<string, string | number | undefined>>,
) {
    const pageSize = ref(toValue(paginated).per_page);

    watch(
        () => toValue(paginated).per_page,
        (value) => {
            pageSize.value = value;
        },
    );

    function navigate(nextPage: number, nextPageSize?: number) {
        router.get(
            route(routeName),
            {
                ...toValue(query),
                page: nextPage,
                per_page: nextPageSize ?? pageSize.value,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }

    return {
        page: computed(() => toValue(paginated).current_page),
        pageSize,
        total: computed(() => toValue(paginated).total),
        setPage(nextPage: number) {
            navigate(nextPage);
        },
        setPageSize(nextPageSize: number) {
            navigate(1, nextPageSize);
        },
    };
}
