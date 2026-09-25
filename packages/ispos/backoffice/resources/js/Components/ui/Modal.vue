<script setup lang="ts">
defineProps<{
    show: boolean;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
    title?: string;
}>();

defineEmits<{ close: [] }>();

const widths = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="$emit('close')" />
            <div
                class="relative w-full rounded-2xl border border-line bg-surface p-6 shadow-soft"
                :class="widths[maxWidth ?? 'md']"
            >
                <div v-if="title || $slots.header" class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 v-if="title" class="font-display text-lg font-semibold text-ink">{{ title }}</h3>
                        <slot name="header" />
                    </div>
                    <button type="button" class="text-ink-muted hover:text-ink" @click="$emit('close')">✕</button>
                </div>
                <slot />
                <div v-if="$slots.footer" class="mt-6 flex justify-end gap-2">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
