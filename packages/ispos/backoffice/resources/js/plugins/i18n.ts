import messages, { type AppLocale, supportedLocales } from '@/locales';
import { createI18n } from 'vue-i18n';

export function resolveLocale(locale?: string): AppLocale {
    if (locale && (supportedLocales as readonly string[]).includes(locale)) {
        return locale as AppLocale;
    }

    return 'en';
}

/** When Google Translate is active, keep UI source text in the default language. */
export function resolveUiLocale(pageProps: {
    locale?: { current?: string; default?: string };
    translation?: { provider?: string; google?: { enabled?: boolean; useWidget?: boolean } };
}): AppLocale {
    const translation = pageProps.translation;

    if (
        translation?.provider === 'google'
        && (translation.google?.enabled || translation.google?.useWidget)
    ) {
        return resolveLocale(pageProps.locale?.default ?? 'en');
    }

    return resolveLocale(pageProps.locale?.current);
}

export function createAppI18n(locale?: string) {
    return createI18n({
        legacy: false,
        locale: resolveLocale(locale),
        fallbackLocale: 'en',
        messages,
    });
}

let appI18n: ReturnType<typeof createAppI18n> | null = null;

export function setAppI18n(instance: ReturnType<typeof createAppI18n>) {
    appI18n = instance;
}

export function translate(key: string, params?: Record<string, unknown>): string {
    if (!appI18n) {
        return key;
    }

    return appI18n.global.t(key, params ?? {});
}
