<script setup lang="ts">
import { useI18n } from 'vue-i18n';

defineProps<{
    showClear?: boolean;
}>();

defineEmits<{
    clear: [];
}>();

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <slot />
            <button
                v-if="showClear"
                type="button"
                class="h-10 rounded-lg px-3 text-sm font-medium text-ink-muted transition hover:bg-surface-raised hover:text-ink"
                @click="$emit('clear')"
            >
                {{ t('common.clearFilters') }}
            </button>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
