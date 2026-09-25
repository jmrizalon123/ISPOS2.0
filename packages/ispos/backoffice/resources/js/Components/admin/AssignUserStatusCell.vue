<script setup lang="ts">
import type { ICellRendererParams } from 'ag-grid-community';
import { computed } from 'vue';

const props = defineProps<{
    params: ICellRendererParams<{ status: string }>;
}>();

const status = computed(() => String(props.params.data?.status ?? '').toLowerCase());

const label = computed(() => String(props.params.data?.status ?? ''));

const variantClass = computed(() =>
    status.value === 'active' ? 'assign-user-status-badge--active' : 'assign-user-status-badge--neutral',
);
</script>

<template>
    <span class="assign-user-status-badge" :class="variantClass">{{ label }}</span>
</template>
