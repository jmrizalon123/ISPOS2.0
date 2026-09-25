<script setup lang="ts">
defineProps<{
    items: Array<{ label: string; value: string }>;
}>();
</script>

<template>
    <div class="form-meta-row">
        <div v-for="item in items" :key="item.label" class="form-meta-chip">
            <span class="form-meta-label">{{ item.label }}</span>
            <span class="form-meta-value" :title="item.value">{{ item.value }}</span>
        </div>
    </div>
</template>
