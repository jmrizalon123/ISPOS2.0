import { shouldShowGoogleWidget } from '@/Composables/useGoogleTranslate';
import { resolveLocale } from '@/plugins/i18n';
import type { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function navTranslationKey(route?: string, label?: string, url?: string): string {
    if (route) {
        return route.replace(/\./g, '_');
    }

    if (url === '__POS_APP_URL__') {
        return 'pos';
    }

    return (label ?? 'item').toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

export function useTranslation() {
    const page = usePage<PageProps>();
    const { t, locale: i18nLocale } = useI18n();

    const localeConfig = computed(() => page.props.locale ?? { current: 'en', supported: {} });
    const translation = computed(() => page.props.translation ?? { provider: 'builtin', google: { enabled: false, useWidget: true } });

    const currentLocale = computed(() => resolveLocale(localeConfig.value.current));

    const usesGoogleWidget = computed(() => shouldShowGoogleWidget(page.props));

    const availableLocales = computed(() => {
        const supported = localeConfig.value.supported ?? {};

        return Object.entries(supported).map(([code, meta]) => ({
            code,
            name: meta.name,
            native: meta.native,
        }));
    });

    function translateNav(route: string | undefined, label: string, url?: string): string {
        const key = navTranslationKey(route, label, url);

        return t(`nav.${key}`, label);
    }

    function translateSection(sectionId: string, fallback: string): string {
        return t(`navSections.${sectionId}`, fallback);
    }

    function switchLocale(code: string) {
        if (code === currentLocale.value) {
            return;
        }

        router.post(
            route('locale.update'),
            { locale: code },
            {
                preserveScroll: true,
                onSuccess: () => {
                    i18nLocale.value = resolveLocale(code);
                },
            },
        );
    }

    return {
        t,
        currentLocale,
        availableLocales,
        usesGoogleWidget,
        translation,
        translateNav,
        translateSection,
        switchLocale,
    };
}
