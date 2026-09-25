import { ref } from 'vue';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface ToastItem {
    id: number;
    message: string;
    variant: ToastVariant;
}

const toasts = ref<ToastItem[]>([]);
let nextId = 0;
const timers = new Map<number, number>();

export function useToastState() {
    return { toasts };
}

export function dismissToast(id: number) {
    toasts.value = toasts.value.filter((item) => item.id !== id);
    const timer = timers.get(id);

    if (timer) {
        window.clearTimeout(timer);
        timers.delete(id);
    }
}

export function showToast(message: string, variant: ToastVariant = 'info', duration = 4200) {
    const id = ++nextId;

    toasts.value = [...toasts.value, { id, message, variant }].slice(-4);

    const timer = window.setTimeout(() => dismissToast(id), duration);
    timers.set(id, timer);

    return id;
}

export const toast = {
    success: (message: string) => showToast(message, 'success'),
    error: (message: string) => showToast(message, 'error'),
    warning: (message: string) => showToast(message, 'warning'),
    info: (message: string) => showToast(message, 'info'),
};
