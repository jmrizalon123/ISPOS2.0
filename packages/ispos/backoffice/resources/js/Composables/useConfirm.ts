import { translate } from '@/plugins/i18n';
import { reactive } from 'vue';

export type ConfirmVariant = 'danger' | 'warning' | 'primary' | 'neutral';

export interface ConfirmOptions {
    title: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: ConfirmVariant;
}

interface ConfirmState extends ConfirmOptions {
    open: boolean;
    resolve: ((value: boolean) => void) | null;
}

const defaults: Omit<ConfirmState, 'resolve'> = {
    open: false,
    title: '',
    message: '',
    confirmLabel: '',
    cancelLabel: '',
    variant: 'primary',
};

export const confirmState = reactive<ConfirmState>({
    ...defaults,
    resolve: null,
});

function closeConfirm(result: boolean) {
    confirmState.resolve?.(result);
    confirmState.open = false;
    confirmState.resolve = null;
}

export function confirmAction(options: ConfirmOptions): Promise<boolean> {
    return new Promise((resolve) => {
        Object.assign(confirmState, {
            ...defaults,
            ...options,
            confirmLabel: options.confirmLabel ?? translate('common.confirm'),
            cancelLabel: options.cancelLabel ?? translate('common.cancel'),
            open: true,
            resolve,
        });
    });
}

export function confirmDelete(itemLabel: string): Promise<boolean> {
    return confirmAction({
        title: translate('confirm.deleteTitle', { item: itemLabel }),
        message: translate('confirm.deleteMessage'),
        confirmLabel: translate('common.delete'),
        variant: 'danger',
    });
}

export function confirmSave(actionLabel: string, itemLabel: string): Promise<boolean> {
    return confirmAction({
        title: translate('confirm.saveTitle', { action: actionLabel, item: itemLabel }),
        message: translate('confirm.saveMessage'),
        confirmLabel: actionLabel,
        variant: 'primary',
    });
}

export function confirmDiscard(itemLabel?: string): Promise<boolean> {
    const label = itemLabel ?? translate('confirm.unsavedChanges');

    return confirmAction({
        title: translate('confirm.discardTitle'),
        message: translate('confirm.discardMessage', { item: label }),
        confirmLabel: translate('common.discard'),
        variant: 'warning',
    });
}

export function confirmReset(itemLabel: string): Promise<boolean> {
    return confirmAction({
        title: translate('confirm.resetTitle', { item: itemLabel }),
        message: translate('confirm.resetMessage'),
        confirmLabel: translate('common.reset'),
        variant: 'warning',
    });
}

export function useConfirmActions() {
    return {
        confirm: () => closeConfirm(true),
        cancel: () => closeConfirm(false),
    };
}
