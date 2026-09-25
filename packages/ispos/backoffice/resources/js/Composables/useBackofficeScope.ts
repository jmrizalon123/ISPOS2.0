import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useBackofficeScope() {
    const page = usePage();

    const scope = computed(() => page.props.auth.context?.scope ?? null);
    const isStoreScope = computed(() => scope.value === 'store');
    const isCompanyScope = computed(() => scope.value === 'company');

    return { scope, isStoreScope, isCompanyScope };
}
