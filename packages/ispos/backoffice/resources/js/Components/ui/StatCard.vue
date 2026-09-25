<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    hint?: string;
    trend?: 'up' | 'down' | 'neutral';
}>();
</script>

<template>
    <div class="group ui-panel p-5 transition hover:shadow-float">
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ label }}</p>
            <span
                v-if="trend === 'up'"
                class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
            >
                Live
            </span>
            <span
                v-else-if="trend === 'down'"
                class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
            >
                Alert
            </span>
        </div>
        <p class="mt-3 font-display text-3xl font-bold tracking-tight text-ink">{{ value }}</p>
        <p v-if="hint" class="mt-2 text-xs leading-relaxed text-ink-muted">{{ hint }}</p>
        <div class="mt-4 h-0.5 w-full overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full w-1/3 rounded-full bg-accent/40 transition-all group-hover:w-2/3 group-hover:bg-accent/60" />
        </div>
    </div>
</template>
