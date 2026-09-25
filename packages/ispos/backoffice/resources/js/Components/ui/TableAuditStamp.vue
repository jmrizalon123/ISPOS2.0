<script setup lang="ts">
import { formatDateTime } from '@/Composables/useDateTime';
import type { AuditableUser } from '@/types';

withDefaults(
    defineProps<{
        user?: AuditableUser | null;
        at?: string | null;
        compact?: boolean;
    }>(),
    { compact: false },
);
</script>

<template>
    <div v-if="compact" class="truncate leading-snug">
        <p class="truncate text-xs">
            <span class="font-medium text-ink">{{ user?.name ?? '—' }}</span>
            <span class="text-ink-muted"> · {{ formatDateTime(at) }}</span>
        </p>
    </div>
    <div v-else class="leading-snug">
        <p class="font-medium text-ink">{{ user?.name ?? '—' }}</p>
        <p class="text-xs text-ink-muted">{{ formatDateTime(at) }}</p>
    </div>
</template>
