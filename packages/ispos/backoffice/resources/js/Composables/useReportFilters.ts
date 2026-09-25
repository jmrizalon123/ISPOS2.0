import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

export function formatReportMoney(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(num) ? num : 0);
}

export function useReportFilters(
    routeName: string,
    initialFilters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string; search?: string },
    companies: Array<{ id: string }>,
    extraKeys: string[] = [],
) {
    const companyId = ref(initialFilters.company_id);
    const storeId = ref(initialFilters.store_id);
    const dateFrom = ref(initialFilters.date_from);
    const dateTo = ref(initialFilters.date_to);
    const status = ref(initialFilters.status);
    const search = ref(initialFilters.search ?? '');

    const showCompanyFilter = computed(() => companies.length > 1);
    const hasActiveFilters = computed(
        () =>
            !!storeId.value ||
            !!search.value ||
            dateFrom.value !== initialFilters.date_from ||
            dateTo.value !== initialFilters.date_to ||
            status.value !== initialFilters.status,
    );

    const filterQuery = computed(() => {
        const payload: Record<string, string | undefined> = {
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            status: status.value || undefined,
        };

        if (extraKeys.includes('search')) {
            payload.search = search.value || undefined;
        }

        return payload;
    });

    function applyFilters() {
        const payload: Record<string, string | undefined> = {
            company_id: companyId.value || undefined,
            store_id: storeId.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            status: status.value || undefined,
        };

        if (extraKeys.includes('search')) {
            payload.search = search.value || undefined;
        }

        router.get(route(routeName), payload, { preserveState: true, replace: true });
    }

    function clearFilters() {
        companyId.value = initialFilters.company_id;
        storeId.value = '';
        dateFrom.value = initialFilters.date_from;
        dateTo.value = initialFilters.date_to;
        status.value = initialFilters.status;
        search.value = '';
        applyFilters();
    }

    watch([companyId, storeId, dateFrom, dateTo, status], applyFilters);
    if (extraKeys.includes('search')) {
        watch(search, applyFilters);
    }

    return {
        companyId,
        storeId,
        dateFrom,
        dateTo,
        status,
        search,
        showCompanyFilter,
        hasActiveFilters,
        filterQuery,
        clearFilters,
        applyFilters,
    };
}
