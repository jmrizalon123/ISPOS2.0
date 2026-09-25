const CLEANUP_DELAYS_MS = [0, 50, 150, 300, 600, 1200, 2500];

function isGoogleTranslateHost(node: Element): boolean {
    if (node.id === 'google_translate_host') {
        return true;
    }

    if (node.classList.contains('google-translate-host')) {
        return true;
    }

    return node.querySelector('#google_translate_host') !== null;
}

export function hideGoogleTranslateBanner(): void {
    document.body.style.top = '0';
    document.body.style.position = 'static';
    document.documentElement.style.marginTop = '0';

    document.querySelectorAll('iframe.goog-te-banner-frame, .goog-te-banner-frame').forEach((node) => {
        node.remove();
    });

    document.querySelectorAll('body > .skiptranslate').forEach((node) => {
        if (isGoogleTranslateHost(node)) {
            return;
        }

        if (node.querySelector('iframe.goog-te-banner-frame, .goog-te-combo')) {
            const hasCombo = node.querySelector('.goog-te-combo');
            const hasBanner = node.querySelector('iframe.goog-te-banner-frame, .goog-te-banner-frame');

            if (hasBanner && !hasCombo) {
                node.remove();
            }

            return;
        }

        if (node.textContent?.includes('Translated to:') || node.textContent?.includes('Show original')) {
            node.remove();
        }
    });
}

export function scheduleGoogleBannerCleanup(): void {
    for (const delay of CLEANUP_DELAYS_MS) {
        window.setTimeout(hideGoogleTranslateBanner, delay);
    }
}

export function startGoogleBannerGuard(): MutationObserver {
    hideGoogleTranslateBanner();

    const observer = new MutationObserver(() => {
        hideGoogleTranslateBanner();
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });

    return observer;
}
