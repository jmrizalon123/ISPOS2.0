<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
        size?: 'sm' | 'md' | 'lg';
        disabled?: boolean;
    }>(),
    {
        type: 'button',
        variant: 'primary',
        size: 'md',
        disabled: false,
    },
);

const classes = computed(() => {
    const base =
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-150 ui-focus disabled:cursor-not-allowed disabled:opacity-50';
    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-4 py-2.5 text-sm',
    };
    const variants = {
        primary:
            'bg-accent text-white shadow-sm hover:bg-accent-hover hover:shadow-md active:scale-[0.98] dark:text-zinc-950',
        secondary:
            'border border-line bg-surface text-ink shadow-sm hover:border-line hover:bg-surface-muted active:scale-[0.98]',
        ghost: 'text-ink-muted hover:bg-surface-muted hover:text-ink',
        danger: 'bg-red-600 text-white shadow-sm hover:bg-red-500 active:scale-[0.98]',
    };

    return [base, sizes[props.size], variants[props.variant]].join(' ');
});
</script>

<template>
    <button :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
