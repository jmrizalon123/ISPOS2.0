<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name?: string | null;
        size?: 'xs' | 'sm' | 'md' | 'lg';
    }>(),
    { src: null, name: null, size: 'sm' },
);

const failed = ref(false);

watch(
    () => props.src,
    () => {
        failed.value = false;
    },
);

const showImage = computed(() => !!props.src && !failed.value);

const initials = computed(() => {
    const parts = (props.name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (!parts.length) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

const sizeClass = computed(
    () =>
        ({
            xs: 'h-6 w-6 text-[10px]',
            sm: 'h-8 w-8 text-[11px]',
            md: 'h-10 w-10 text-xs',
            lg: 'h-20 w-20 text-2xl',
        })[props.size],
);
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-bold uppercase ring-1"
        :class="[sizeClass, showImage ? 'bg-surface-muted ring-line' : 'bg-accent-soft text-accent ring-accent/20']"
        :title="name ?? undefined"
    >
        <img
            v-if="showImage"
            :src="src!"
            :alt="name ?? ''"
            class="h-full w-full object-cover"
            @error="failed = true"
        />
        <span v-else>{{ initials }}</span>
    </span>
</template>
