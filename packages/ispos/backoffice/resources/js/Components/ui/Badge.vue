<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{ variant?: 'neutral' | 'success' | 'warning' | 'danger' | 'accent' }>(),
    { variant: 'neutral' },
);

const classes = computed(() => {
    const map = {
        neutral: 'border border-line bg-surface-muted/80 text-ink-muted',
        success: 'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300',
        warning: 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
        danger: 'border border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300',
        accent: 'border border-teal-200 bg-accent-soft text-teal-800 dark:border-teal-900 dark:text-teal-200',
    };
    return map[props.variant];
});
</script>

<template>
    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide" :class="classes">
        <slot />
    </span>
</template>
