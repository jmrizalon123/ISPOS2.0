<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{ variant?: 'info' | 'success' | 'warning' | 'danger' }>(),
    { variant: 'info' },
);

const classes = computed(() => {
    const map = {
        info: 'border-teal-200 bg-teal-50 text-teal-900 dark:border-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
        warning: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
        danger: 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100',
    };
    return map[props.variant];
});
</script>

<template>
    <div class="rounded-xl border px-4 py-3 text-sm" :class="classes">
        <slot />
    </div>
</template>
