import { toast } from '@/Composables/useToast';
import { translate } from '@/plugins/i18n';
import { router } from '@inertiajs/vue3';

export function setupInertiaFeedback() {
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash as { success?: string; error?: string } | undefined;

        if (flash?.success) {
            toast.success(String(flash.success));
        }

        if (flash?.error) {
            toast.error(String(flash.error));
        }
    });

    router.on('error', (event) => {
        const errors = event.detail.errors;

        if (errors && Object.keys(errors).length > 0) {
            const first = Object.values(errors).find((message) => Boolean(message));
            toast.error(first ? String(first) : translate('messages.fixErrors'));
            return;
        }

        toast.error(translate('messages.genericError'));
    });
}
