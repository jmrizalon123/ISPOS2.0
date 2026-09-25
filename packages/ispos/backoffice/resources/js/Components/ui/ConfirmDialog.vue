<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import { confirmState, useConfirmActions } from '@/Composables/useConfirm';
import { computed, onMounted, onUnmounted, watch } from 'vue';

const { confirm, cancel } = useConfirmActions();

const iconClasses = computed(() => {
    const map = {
        danger: 'bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-300',
        warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        primary: 'bg-accent-soft text-accent',
        neutral: 'bg-surface-muted text-ink-muted',
    };

    return map[confirmState.variant ?? 'primary'];
});

const confirmVariant = computed(() => {
    return confirmState.variant === 'danger' ? 'danger' : 'primary';
});

function onKeydown(event: KeyboardEvent) {
    if (!confirmState.open) {
        return;
    }

    if (event.key === 'Escape') {
        cancel();
    }
}

watch(
    () => confirmState.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="confirmState.open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-950/45 backdrop-blur-sm" @click="cancel" />

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="confirmState.open"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-line bg-surface shadow-soft"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="confirmState.title"
                    >
                        <div class="p-6">
                            <div class="flex gap-4">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                    :class="iconClasses"
                                >
                                    <svg
                                        v-if="confirmState.variant === 'danger'"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                    </svg>
                                    <svg
                                        v-else-if="confirmState.variant === 'warning'"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3 2.5 19.5A1 1 0 0 0 3.4 21h17.2a1 1 0 0 0 .9-1.5L12 3Z" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-display text-lg font-semibold tracking-tight text-ink">
                                        {{ confirmState.title }}
                                    </h3>
                                    <p v-if="confirmState.message" class="mt-2 text-sm leading-relaxed text-ink-muted">
                                        {{ confirmState.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-line bg-surface-muted/40 px-6 py-4">
                            <Button variant="secondary" @click="cancel">{{ confirmState.cancelLabel }}</Button>
                            <Button :variant="confirmVariant" @click="confirm">{{ confirmState.confirmLabel }}</Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
