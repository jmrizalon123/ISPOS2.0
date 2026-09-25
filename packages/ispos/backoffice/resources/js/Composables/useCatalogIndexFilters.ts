import { router } from '@inertiajs/vue3';
import { computed, ref, watch, type Ref } from 'vue';

export interface CatalogFilterCompany {
    id: string;
    name: string;
    display_name?: string | null;
}

export interface CatalogFilterStore {
    id: string;
    store_name: string;
    store_code: string;
    company_id: string;
}

type FilterRefs = Record<string, Ref<string>>;

export function useCatalogIndexFilters(
    routeName: string,
    initial: Record<string, string>,
    keys: string[],
    companies: CatalogFilterCompany[],
    stores: CatalogFilterStore[] = [],
) {
    const refs = Object.fromEntries(keys.map((key) => [key, ref(initial[key] ?? '')])) as FilterRefs;

    const showCompanyFilter = computed(() => companies.length > 1);
    const showStoreFilter = computed(() => stores.length > 0);

    const filteredStores = computed(() => {
        const companyId = refs.company_id?.value ?? '';

        return stores.filter((store) => !companyId || store.company_id === companyId);
    });

    const hasActiveFilters = computed(() => keys.some((key) => !!refs[key]?.value));

    const filterQuery = computed(() => {
        const params: Record<string, string | undefined> = {};

        for (const key of keys) {
            params[key] = refs[key]?.value || undefined;
        }

        return params;
    });

    function applyFilters() {
        const params: Record<string, string | undefined> = {};

        for (const key of keys) {
            params[key] = refs[key]?.value || undefined;
        }

        router.get(route(routeName), params, { preserveState: true, replace: true });
    }

    function clearFilters() {
        for (const key of keys) {
            if (refs[key]) {
                refs[key].value = '';
            }
        }

        applyFilters();
    }

    watch(
        () => keys.map((key) => refs[key]?.value),
        applyFilters,
    );

    if (refs.company_id) {
        watch(refs.company_id, () => {
            if (refs.store_id?.value && !filteredStores.value.some((store) => store.id === refs.store_id!.value)) {
                refs.store_id!.value = '';
            }

            if (refs.category_id) {
                refs.category_id.value = '';
            }

            if (refs.brand_id) {
                refs.brand_id.value = '';
            }

            if (refs.sales_plan_id) {
                refs.sales_plan_id.value = '';
            }
        });
    }

    if (refs.store_id && refs.company_id) {
        watch(refs.store_id, (value) => {
            if (!value) {
                return;
            }

            const store = stores.find((item) => item.id === value);
            if (store && showCompanyFilter.value && !refs.company_id!.value) {
                refs.company_id!.value = store.company_id;
            }
        });
    }

    return {
        refs,
        search: refs.search,
        stockStatus: refs.stock_status,
        companyId: refs.company_id,
        storeId: refs.store_id,
        status: refs.status,
        categoryId: refs.category_id,
        brandId: refs.brand_id,
        salesPlanId: refs.sales_plan_id,
        showCompanyFilter,
        showStoreFilter,
        filteredStores,
        hasActiveFilters,
        filterQuery,
        clearFilters,
    };
}
