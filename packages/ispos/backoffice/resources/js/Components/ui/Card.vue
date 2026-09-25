<script setup lang="ts">
withDefaults(
    defineProps<{ title?: string; description?: string; compact?: boolean }>(),
    { compact: false },
);
</script>

<template>
    <div class="ui-panel" :class="compact ? 'p-4' : 'p-5 sm:p-6'">
        <div
            v-if="title || description || $slots.header"
            class="flex items-start justify-between gap-3"
            :class="compact ? 'mb-3' : 'mb-5'"
        >
            <div>
                <h3 v-if="title" class="font-display text-base font-semibold tracking-tight text-ink">{{ title }}</h3>
                <p v-if="description" class="mt-1 text-sm leading-relaxed text-ink-muted">{{ description }}</p>
                <slot name="header" />
            </div>
            <slot name="actions" />
        </div>
        <slot />
    </div>
</template>
