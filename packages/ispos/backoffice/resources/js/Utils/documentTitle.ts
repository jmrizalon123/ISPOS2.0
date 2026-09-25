const fallbackAppName = import.meta.env.VITE_APP_NAME || 'iSPOS';

/** Marks the favicon link we own, so a hand-written one in the layout is never orphaned. */
const FAVICON_ATTRIBUTE = 'data-app-favicon';

let pageTitle = '';
let appName = fallbackAppName;
let companyName: string | null = null;
let logoUrl: string | null = null;

let previewAppName: string | null = null;
/** `undefined` means no preview is active; `null` means preview an empty logo. */
let previewLogoUrl: string | null | undefined;

function normalize(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}

function activeAppName(): string {
    return previewAppName ?? appName;
}

function activeLogoUrl(): string | null {
    return previewLogoUrl === undefined ? logoUrl : previewLogoUrl;
}

function applyDocumentTitle(): void {
    const app = activeAppName();
    const base = pageTitle ? `${pageTitle} - ${app}` : app;

    document.title = companyName ? `${base} | ${companyName}` : base;
}

function faviconMimeType(url: string): string | null {
    const extension = url.split(/[?#]/)[0].split('.').pop()?.toLowerCase();

    switch (extension) {
        case 'svg':
            return 'image/svg+xml';
        case 'png':
            return 'image/png';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'webp':
            return 'image/webp';
        case 'ico':
            return 'image/x-icon';
        default:
            return null;
    }
}

function applyFavicon(): void {
    const url = activeLogoUrl();
    const existing = document.head.querySelector<HTMLLinkElement>(`link[${FAVICON_ATTRIBUTE}]`);

    if (!url) {
        existing?.remove();

        return;
    }

    const link = existing ?? document.createElement('link');
    const mimeType = faviconMimeType(url);

    link.rel = 'icon';
    link.setAttribute(FAVICON_ATTRIBUTE, '');

    if (mimeType) {
        link.type = mimeType;
    } else {
        link.removeAttribute('type');
    }

    if (link.getAttribute('href') !== url) {
        link.setAttribute('href', url);
    }

    if (!existing) {
        document.head.appendChild(link);
    }
}

export function setDocumentPageTitle(title: string): string {
    pageTitle = normalize(title) ?? '';
    applyDocumentTitle();

    return document.title;
}

/** Applies the shared branding from an Inertia visit to the tab title and favicon. */
export function updateBranding(branding: {
    appName?: unknown;
    companyName?: unknown;
    logoUrl?: unknown;
}): void {
    appName = normalize(branding.appName) ?? appName;
    companyName = normalize(branding.companyName);
    logoUrl = normalize(branding.logoUrl);

    applyDocumentTitle();
    applyFavicon();
}

/** Live preview while editing iSPOS branding, before the changes are saved. */
export function setBrandingPreview(preview: { appName?: string | null; logoUrl?: string | null }): void {
    if ('appName' in preview) {
        previewAppName = normalize(preview.appName);
    }

    if ('logoUrl' in preview) {
        previewLogoUrl = normalize(preview.logoUrl);
    }

    applyDocumentTitle();
    applyFavicon();
}

export function clearBrandingPreview(): void {
    previewAppName = null;
    previewLogoUrl = undefined;

    applyDocumentTitle();
    applyFavicon();
}
