<script setup lang="ts">
defineProps<{ lines?: number }>();
</script>

<template>
    <div class="animate-pulse space-y-3">
        <div
            v-for="n in lines ?? 3"
            :key="n"
            class="h-4 rounded bg-surface-muted"
            :style="{ width: `${70 + ((n * 13) % 30)}%` }"
        />
    </div>
</template>
