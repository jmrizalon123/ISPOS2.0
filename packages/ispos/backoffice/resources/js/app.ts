import '../css/app.css';
import './bootstrap';

import { setupInertiaFeedback } from '@/Composables/setupInertiaFeedback';
import { createAppI18n, resolveUiLocale, setAppI18n } from '@/plugins/i18n';
import { setDocumentPageTitle, updateBranding } from '@/Utils/documentTitle';
import { createInertiaApp, router } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

function applyBranding(props: PageProps): void {
    updateBranding({
        appName: props.app?.name,
        companyName: props.auth?.user?.company?.name,
        logoUrl: props.app?.branding?.logo_url,
    });
}

setupInertiaFeedback();

router.on('invalid', (event) => {
    if (event.detail.response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

router.on('success', (event) => {
    applyBranding(event.detail.page.props);

    const token = event.detail.page.props.app?.csrf_token;

    if (typeof token === 'string') {
        const csrfMeta = document.head.querySelector('meta[name="csrf-token"]');

        if (csrfMeta instanceof HTMLMetaElement) {
            csrfMeta.content = token;
        }

        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    }
});

createInertiaApp({
    title: (title) => setDocumentPageTitle(title ?? ''),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyBranding(props.initialPage.props);

        const i18n = createAppI18n(resolveUiLocale(props.initialPage.props as Parameters<typeof resolveUiLocale>[0]));
        setAppI18n(i18n);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);

        router.on('success', (event) => {
            const nextLocale = resolveUiLocale(event.detail.page.props as Parameters<typeof resolveUiLocale>[0]);

            if (i18n.global.locale.value !== nextLocale) {
                i18n.global.locale.value = nextLocale;
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});
