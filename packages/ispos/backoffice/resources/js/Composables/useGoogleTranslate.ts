import type { PageProps } from '@/types';
import { scheduleGoogleBannerCleanup } from '@/Utils/googleTranslateBanner';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

declare global {
    interface Window {
        googleTranslateElementInit?: () => void;
        google?: {
            translate: {
                TranslateElement: {
                    new (options: Record<string, unknown>, elementId: string): unknown;
                    InlineLayout: {
                        SIMPLE: number;
                    };
                };
            };
        };
    }
}

const ready = ref(false);
const switching = ref(false);
let loading: Promise<void> | null = null;
let scriptEl: HTMLScriptElement | null = null;

const HOST_ID = 'google_translate_host';

function translationProps(page: PageProps) {
    return page.translation ?? { provider: 'builtin', google: { enabled: false, useWidget: true } };
}

export function isGoogleTranslationActive(page: PageProps): boolean {
    const translation = translationProps(page);

    if (translation.provider !== 'google') {
        return false;
    }

    return translation.google.enabled || translation.google.useWidget;
}

export function shouldShowGoogleWidget(page: PageProps): boolean {
    const translation = translationProps(page);

    return translation.provider === 'google' && translation.google.useWidget;
}

function waitForCombo(timeoutMs = 10000): Promise<HTMLSelectElement | null> {
    return new Promise((resolve) => {
        const started = Date.now();

        const check = () => {
            const combo = document.querySelector<HTMLSelectElement>('.goog-te-combo');

            if (combo) {
                resolve(combo);

                return;
            }

            if (Date.now() - started >= timeoutMs) {
                resolve(null);

                return;
            }

            window.setTimeout(check, 100);
        };

        check();
    });
}

function readActiveGoogleCode(): string {
    const match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);

    if (!match?.[1]) {
        return '';
    }

    const parts = decodeURIComponent(match[1]).split('/').filter(Boolean);

    return parts.length >= 2 ? parts[parts.length - 1] : '';
}

function googleCodeForLocale(localeCode: string, supported: Record<string, { google: string }>): string {
    if (localeCode === 'en') {
        return '';
    }

    return supported[localeCode]?.google ?? localeCode;
}

function localeForGoogleCode(googleCode: string, supported: Record<string, { google: string }>): string {
    if (!googleCode) {
        return 'en';
    }

    const match = Object.entries(supported).find(([, meta]) => meta.google === googleCode);

    return match?.[0] ?? 'en';
}

function setGoogTransCookie(pageLang: string, googleCode: string): void {
    const cookieValue = googleCode ? `/${pageLang}/${googleCode}` : '';
    const hostname = window.location.hostname;
    const base = `googtrans=${cookieValue};path=/`;

    document.cookie = base;

    if (hostname && hostname !== 'localhost' && hostname !== '127.0.0.1') {
        document.cookie = `${base};domain=${hostname}`;
    }
}

function clearGoogTransCookie(): void {
    const expires = 'Thu, 01 Jan 1970 00:00:00 UTC';
    const hostname = window.location.hostname;

    document.cookie = `googtrans=;expires=${expires};path=/`;

    if (hostname && hostname !== 'localhost' && hostname !== '127.0.0.1') {
        document.cookie = `googtrans=;expires=${expires};path=/;domain=${hostname}`;
    }
}

function isPageTranslated(): boolean {
    return (
        document.documentElement.classList.contains('translated-ltr')
        || document.documentElement.classList.contains('translated-rtl')
    );
}

function triggerComboChange(combo: HTMLSelectElement, googleCode: string): boolean {
    const target = googleCode || '';

    for (const option of combo.options) {
        if (option.value === target) {
            combo.value = option.value;
            combo.dispatchEvent(new Event('change', { bubbles: true }));

            return true;
        }
    }

    combo.value = target;
    combo.dispatchEvent(new Event('change', { bubbles: true }));

    return combo.value === target || target === '';
}

export function useGoogleTranslate() {
    const page = usePage<PageProps>();

    const config = computed(() => page.props.translation?.google);
    const supported = computed(() => page.props.locale?.supported ?? {});

    const isActive = computed(() => isGoogleTranslationActive(page.props));

    const activeLocale = computed(() => localeForGoogleCode(readActiveGoogleCode(), supported.value));

    async function ensureLoaded(): Promise<void> {
        if (!isActive.value || !config.value) {
            return;
        }

        if (document.querySelector('.goog-te-combo')) {
            ready.value = true;

            return;
        }

        if (loading) {
            await loading;

            return;
        }

        loading = new Promise<void>((resolve) => {
            const finish = async () => {
                await waitForCombo();
                ready.value = true;
                resolve();
            };

            const mountWidget = () => {
                if (!window.google?.translate?.TranslateElement) {
                    resolve();

                    return;
                }

                if (!document.getElementById(HOST_ID)) {
                    resolve();

                    return;
                }

                if (!document.querySelector('.goog-te-combo')) {
                    new window.google.translate.TranslateElement(
                        {
                            pageLanguage: config.value?.pageLanguage ?? 'en',
                            includedLanguages: config.value?.includedLanguages ?? 'en,zh-CN,tl',
                            layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
                        },
                        HOST_ID,
                    );
                }

                void finish();
            };

            if (window.google?.translate?.TranslateElement) {
                mountWidget();

                return;
            }

            window.googleTranslateElementInit = mountWidget;

            if (!scriptEl) {
                scriptEl = document.createElement('script');
                scriptEl.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                scriptEl.async = true;
                document.body.appendChild(scriptEl);
            }
        });

        await loading;
    }

    async function switchLanguage(localeCode: string): Promise<void> {
        if (switching.value) {
            return;
        }

        switching.value = true;

        try {
            const pageLang = config.value?.pageLanguage ?? 'en';
            const googleCode = googleCodeForLocale(localeCode, supported.value);

            if (googleCode) {
                setGoogTransCookie(pageLang, googleCode);
            } else {
                clearGoogTransCookie();
            }

            await ensureLoaded();

            const combo = await waitForCombo();

            if (combo && triggerComboChange(combo, googleCode)) {
                await new Promise((resolve) => window.setTimeout(resolve, 400));

                const translated = isPageTranslated();
                const wantsTranslation = localeCode !== 'en';

                if ((wantsTranslation && translated) || (!wantsTranslation && !translated)) {
                    scheduleGoogleBannerCleanup();

                    return;
                }
            }

            scheduleGoogleBannerCleanup();
            window.location.reload();

            return;
        } finally {
            switching.value = false;
        }
    }

    return {
        isActive,
        ready,
        switching,
        activeLocale,
        ensureLoaded,
        switchLanguage,
    };
}
