import { navTranslationKey, useTranslation } from '@/Composables/useTranslation';
import { navItems, navSections, utilityNavItems, type NavItemConfig } from '@/config/navigation';
import { usePermissions } from '@/Composables/usePermissions';
import { usePage } from '@inertiajs/vue3';
import { computed, type Ref } from 'vue';

export interface NavItem extends NavItemConfig {
    href: string;
}

export interface NavSection {
    id: string;
    label: string;
    items: NavItem[];
}

function resolveNavItem(item: NavItemConfig, posAppUrl: string): NavItem {
    const href = item.url
        ? item.url.replace('__POS_APP_URL__', posAppUrl)
        : route(item.route!);

    return {
        ...item,
        href,
    };
}

function matchesQuery(item: NavItem, query: string): boolean {
    const q = query.trim().toLowerCase();
    if (!q) {
        return true;
    }

    const labelKey = navTranslationKey(item.route, item.label, item.url);

    if (item.label.toLowerCase().includes(q) || labelKey.includes(q.replace(/\s+/g, '_'))) {
        return true;
    }

    if (item.section.toLowerCase().includes(q)) {
        return true;
    }

    return (item.keywords ?? []).some((keyword) => keyword.includes(q));
}

export function useNavigation(searchQuery?: Ref<string>) {
    const { can } = usePermissions();
    const { translateNav, translateSection } = useTranslation();
    const page = usePage();
    const posAppUrl = computed(() => (page.props.app as { pos_url?: string })?.pos_url ?? 'http://127.0.0.1:8003/pos');
    const isCompanyScope = computed(() => page.props.auth.context?.scope === 'company');

    const isItemVisible = (item: NavItemConfig): boolean => {
        if (item.companyScopeOnly && !isCompanyScope.value) {
            return false;
        }

        return can(item.permission);
    };

    const visibleItems = computed(() =>
        navItems.filter(isItemVisible).map((item) => resolveNavItem(item, posAppUrl.value)),
    );

    const utilityItems = computed(() => utilityNavItems.map((item) => resolveNavItem(item, posAppUrl.value)));

    const searchableItems = computed(() => [...visibleItems.value, ...utilityItems.value]);

    const filteredItems = computed(() => {
        const query = searchQuery?.value ?? '';
        if (!query.trim()) {
            return visibleItems.value;
        }

        return visibleItems.value.filter((item) => matchesQuery(item, query));
    });

    const filteredSections = computed((): NavSection[] => {
        const items = filteredItems.value;

        return navSections
            .map((section) => ({
                id: section.id,
                label: translateSection(section.id, section.label),
                items: items
                    .filter((item) => item.section === section.id)
                    .map((item) => ({
                        ...item,
                        label: translateNav(item.route, item.label, item.url),
                    })),
            }))
            .filter((section) => section.items.length > 0);
    });

    const paletteItems = computed(() => {
        const query = searchQuery?.value ?? '';

        return searchableItems.value
            .filter((item) => matchesQuery(item, query))
            .map((item) => ({
                ...item,
                label: translateNav(item.route, item.label, item.url),
            }));
    });

    function isActive(match: string): boolean {
        return route().current(match);
    }

    return {
        visibleItems,
        utilityItems,
        searchableItems,
        filteredItems,
        filteredSections,
        paletteItems,
        isActive,
    };
}
