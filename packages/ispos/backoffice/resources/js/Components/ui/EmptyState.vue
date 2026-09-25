<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    actionLabel?: string;
}>();

defineEmits<{ action: [] }>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line bg-surface-muted/30 px-6 py-16 text-center">
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-accent-soft text-accent">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        </div>
        <h3 class="font-display text-lg font-semibold tracking-tight text-ink">{{ title }}</h3>
        <p v-if="description" class="mt-2 max-w-md text-sm leading-relaxed text-ink-muted">{{ description }}</p>
        <button
            v-if="actionLabel"
            type="button"
            class="mt-5 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-accent-hover dark:text-zinc-950"
            @click="$emit('action')"
        >
            {{ actionLabel }}
        </button>
        <slot />
    </div>
</template>
