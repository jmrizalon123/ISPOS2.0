<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { dismissToast, useToastState, type ToastVariant } from '@/Composables/useToast';
import { computed } from 'vue';

const { toasts } = useToastState();
const { t } = useLocale();

const variantStyles: Record<ToastVariant, string> = {
    success: 'border-emerald-200/80 bg-emerald-50/95 text-emerald-950 dark:border-emerald-900/80 dark:bg-emerald-950/90 dark:text-emerald-100',
    error: 'border-red-200/80 bg-red-50/95 text-red-950 dark:border-red-900/80 dark:bg-red-950/90 dark:text-red-100',
    warning: 'border-amber-200/80 bg-amber-50/95 text-amber-950 dark:border-amber-900/80 dark:bg-amber-950/90 dark:text-amber-100',
    info: 'border-line bg-surface/95 text-ink',
};

function iconPath(variant: ToastVariant) {
    if (variant === 'success') {
        return 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
    }

    if (variant === 'error') {
        return 'M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
    }

    if (variant === 'warning') {
        return 'M12 9v4m0 4h.01M12 3 2.5 19.5A1 1 0 0 0 3.4 21h17.2a1 1 0 0 0 .9-1.5L12 3Z';
    }

    return 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z';
}

const iconClasses = computed(() => ({
    success: 'text-emerald-600 dark:text-emerald-300',
    error: 'text-red-600 dark:text-red-300',
    warning: 'text-amber-600 dark:text-amber-300',
    info: 'text-accent',
}));
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed right-6 top-6 z-[65] flex w-max max-w-sm flex-col gap-3">
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="-translate-y-1 opacity-0"
                move-class="transition duration-200"
            >
                <div
                    v-for="item in toasts"
                    :key="item.id"
                    class="pointer-events-auto overflow-hidden rounded-xl border shadow-soft backdrop-blur-md"
                    :class="variantStyles[item.variant]"
                >
                    <div class="flex items-start gap-3 px-4 py-3.5">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0"
                            :class="iconClasses[item.variant]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.75"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.variant)" />
                        </svg>
                        <p class="flex-1 text-sm font-medium leading-relaxed">{{ item.message }}</p>
                        <button
                            type="button"
                            class="shrink-0 rounded-md p-1 opacity-60 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
                            :aria-label="t('messages.dismiss')"
                            @click="dismissToast(item.id)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
