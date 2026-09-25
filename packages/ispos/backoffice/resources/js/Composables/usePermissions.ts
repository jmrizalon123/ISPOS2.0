import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => (page.props.auth.user?.permissions ?? []) as string[]);
    const roles = computed(() => (page.props.auth.user?.roles ?? []) as string[]);

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function canAny(list: string[]): boolean {
        return list.some((permission) => can(permission));
    }

    function hasRole(role: string): boolean {
        return roles.value.includes(role);
    }

    return { permissions, roles, can, canAny, hasRole };
}
