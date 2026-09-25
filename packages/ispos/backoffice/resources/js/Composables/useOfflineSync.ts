import { computed, ref } from 'vue';

const STORAGE_TOKEN = 'ispos-pos-device-token';
const STORAGE_DEVICE = 'ispos-pos-device';
const STORAGE_CATALOG = 'ispos-offline-catalog';
const STORAGE_OUTBOX = 'ispos-offline-outbox';

export interface OfflineDeviceState {
    id: string;
    name: string;
    store_id: string;
    register_id: string;
}

export interface OfflineSalePayload {
    client_uuid: string;
    pos_shift_id: string;
    completed_at: string;
    subtotal: number;
    tax_total: number;
    discount_total?: number;
    grand_total: number;
    customer_id?: string | null;
    promotion_id?: string | null;
    lines: Array<Record<string, unknown>>;
    payment?: { method?: string; amount?: number; reference?: string | null };
}

function readJson<T>(key: string, fallback: T): T {
    try {
        const raw = localStorage.getItem(key);
        return raw ? (JSON.parse(raw) as T) : fallback;
    } catch {
        return fallback;
    }
}

function writeJson(key: string, value: unknown) {
    localStorage.setItem(key, JSON.stringify(value));
}

function deviceFingerprint(): string {
    const existing = localStorage.getItem('ispos-device-fingerprint');
    if (existing) {
        return existing;
    }

    const fingerprint = crypto.randomUUID();
    localStorage.setItem('ispos-device-fingerprint', fingerprint);

    return fingerprint;
}

export function useOfflineSync() {
    const syncing = ref(false);
    const lastError = ref<string | null>(null);
    const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

    if (typeof window !== 'undefined') {
        window.addEventListener('online', () => {
            isOnline.value = true;
        });
        window.addEventListener('offline', () => {
            isOnline.value = false;
        });
    }

    const deviceToken = computed(() => localStorage.getItem(STORAGE_TOKEN));
    const registeredDevice = computed(() => readJson<OfflineDeviceState | null>(STORAGE_DEVICE, null));
    const outboxCount = computed(() => readJson<OfflineSalePayload[]>(STORAGE_OUTBOX, []).length);
    const isRegistered = computed(() => Boolean(deviceToken.value && registeredDevice.value));

    async function registerDevice(registerId: string, name: string) {
        const response = await window.axios.post('/api/v1/devices/register', {
            register_id: registerId,
            name,
            fingerprint: deviceFingerprint(),
        });

        const data = response.data?.data;
        localStorage.setItem(STORAGE_TOKEN, data.token);
        writeJson(STORAGE_DEVICE, data.device);

        return data.device as OfflineDeviceState;
    }

    async function bootstrapCatalog() {
        if (!deviceToken.value) {
            throw new Error('Device is not registered for sync.');
        }

        syncing.value = true;
        lastError.value = null;

        try {
            const response = await window.axios.get('/api/v1/sync/bootstrap', {
                headers: { Authorization: `Bearer ${deviceToken.value}` },
            });

            writeJson(STORAGE_CATALOG, response.data?.data ?? null);

            return response.data?.data;
        } catch (error: unknown) {
            lastError.value = error instanceof Error ? error.message : 'Bootstrap failed';
            throw error;
        } finally {
            syncing.value = false;
        }
    }

    function queueOfflineSale(payload: OfflineSalePayload) {
        const outbox = readJson<OfflineSalePayload[]>(STORAGE_OUTBOX, []);
        outbox.push(payload);
        writeJson(STORAGE_OUTBOX, outbox);
    }

    async function flushOutbox() {
        if (!deviceToken.value) {
            return { accepted: [], duplicates: [], failed: [] };
        }

        const outbox = readJson<OfflineSalePayload[]>(STORAGE_OUTBOX, []);
        if (outbox.length === 0) {
            return { accepted: [], duplicates: [], failed: [] };
        }

        syncing.value = true;
        lastError.value = null;

        try {
            const response = await window.axios.post(
                '/api/v1/sync/push',
                { sales: outbox },
                { headers: { Authorization: `Bearer ${deviceToken.value}` } },
            );

            const result = response.data?.data ?? {};
            const failedCount = (result.failed ?? []).length;

            if (failedCount === 0) {
                writeJson(STORAGE_OUTBOX, []);
            } else {
                writeJson(STORAGE_OUTBOX, outbox.slice(failedCount));
            }

            return result;
        } catch (error: unknown) {
            lastError.value = error instanceof Error ? error.message : 'Sync push failed';
            throw error;
        } finally {
            syncing.value = false;
        }
    }

    async function syncNow() {
        await bootstrapCatalog();
        return flushOutbox();
    }

    function clearRegistration() {
        localStorage.removeItem(STORAGE_TOKEN);
        localStorage.removeItem(STORAGE_DEVICE);
    }

    return {
        syncing,
        lastError,
        isOnline,
        isRegistered,
        registeredDevice,
        outboxCount,
        registerDevice,
        bootstrapCatalog,
        queueOfflineSale,
        flushOutbox,
        syncNow,
        clearRegistration,
    };
}
