<script setup lang="ts">
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import { useI18n } from 'vue-i18n';

defineProps<{
    show: boolean;
    userName: string;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    highlightStoreId?: string | null;
}>();

defineEmits<{ close: [] }>();

const { t } = useI18n();
</script>

<template>
    <Modal
        :show="show"
        :title="t('storeEmployees.storeAccessModalTitle', { name: userName })"
        max-width="md"
        @close="$emit('close')"
    >
        <ul class="max-h-80 divide-y divide-line overflow-y-auto">
            <li
                v-for="store in stores"
                :key="store.id"
                class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium text-ink">{{ store.store_name }}</p>
                    <p class="truncate text-xs text-ink-muted">{{ store.store_code }}</p>
                </div>
                <Badge v-if="highlightStoreId && store.id === highlightStoreId" variant="accent" class="shrink-0">
                    {{ t('storeEmployees.currentStore') }}
                </Badge>
            </li>
        </ul>
    </Modal>
</template>
