/**
 * Silent receipt print — no popup / no print dialog in IsposPosHost.
 *
 * Host path: fetch plain text → chrome.webview / __isposPost → Windows PrintDocument.
 * Browser fallback: hidden iframe HTML print (OS dialog; browsers cannot fully silent-print).
 */
import { toast } from '@/Composables/useToast';

type HostWindow = Window & {
    chrome?: { webview?: { postMessage: (message: unknown) => void } };
    __isposHost?: boolean;
    __isposPost?: (payload: unknown) => boolean;
};

function hostWindow(): HostWindow {
    return window as HostWindow;
}

export function isPosHostShell(): boolean {
    const w = hostWindow();
    return Boolean(w.__isposHost || w.chrome?.webview);
}

export function pingPosHost(): void {
    postToHost({ type: 'ping' });
}

function postToHost(payload: Record<string, unknown>): boolean {
    const w = hostWindow();

    try {
        if (typeof w.__isposPost === 'function') {
            return w.__isposPost(payload) === true;
        }

        if (w.chrome?.webview?.postMessage) {
            // Stringify so the host always receives a stable JSON string.
            w.chrome.webview.postMessage(JSON.stringify(payload));
            return true;
        }
    } catch (error) {
        console.error('[ispos] postToHost failed', error);
    }

    return false;
}

/** Prevent duplicate jobs when checkout + flash watcher both fire. */
const recentlyPrinted = new Map<string, number>();
const PRINT_DEDUPE_MS = 8000;

export async function printReceiptSilently(saleId: string, change?: string | null): Promise<void> {
    const now = Date.now();
    const last = recentlyPrinted.get(saleId) ?? 0;
    if (now - last < PRINT_DEDUPE_MS) {
        console.info('[ispos] skip duplicate print', saleId);
        return;
    }
    recentlyPrinted.set(saleId, now);

    const params = new URLSearchParams();
    if (change != null && change !== '') {
        params.set('change', String(change));
    }
    const qs = params.toString();
    const textUrl = `${route('pos.sales.receipt.text', saleId)}${qs ? `?${qs}` : ''}`;
    const htmlUrl = `${route('pos.sales.receipt.print', saleId)}?embedded=1${qs ? `&${qs}` : ''}`;

    console.info('[ispos] printReceiptSilently', {
        saleId,
        inHost: isPosHostShell(),
        textUrl,
    });

    if (isPosHostShell()) {
        try {
            const response = await fetch(textUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'text/plain' },
            });

            if (!response.ok) {
                throw new Error(`Receipt text HTTP ${response.status}`);
            }

            const text = await response.text();
            if (!text.includes('Sale #') && !text.includes('TOTAL')) {
                throw new Error('Receipt text missing expected content');
            }

            const ok = postToHost({
                type: 'silent-print-text',
                text,
            });

            if (!ok) {
                throw new Error('Host bridge postMessage returned false');
            }

            toast.success('Sending receipt to printer…');
            return;
        } catch (error) {
            console.error('[ispos] host silent print failed', error);
            toast.error('Silent print failed. Check print.log next to IsposPosHost.exe');
            // Fall through to iframe as last resort.
        }
    }

    // Browser / fallback path (will show OS print dialog).
    try {
        const response = await fetch(htmlUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'text/html' },
        });
        if (!response.ok) {
            throw new Error(`Receipt HTML HTTP ${response.status}`);
        }
        printViaHiddenIframeHtml(await response.text());
    } catch (error) {
        console.error('[ispos] iframe print failed', error);
        toast.error('Could not print receipt.');
    }
}

function printViaHiddenIframeHtml(html: string): void {
    const existing = document.getElementById('ispos-silent-receipt-frame');
    if (existing) {
        existing.remove();
    }

    const iframe = document.createElement('iframe');
    iframe.id = 'ispos-silent-receipt-frame';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText =
        'position:fixed;left:-10000px;top:0;width:80mm;height:200mm;border:0;opacity:0;pointer-events:none;';
    iframe.srcdoc = html;

    iframe.onload = () => {
        window.setTimeout(() => {
            try {
                iframe.contentWindow?.focus();
                iframe.contentWindow?.print();
            } finally {
                window.setTimeout(() => iframe.remove(), 2000);
            }
        }, 300);
    };

    document.body.appendChild(iframe);
}
