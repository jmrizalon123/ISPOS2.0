<script setup lang="ts">
import Select from '@/Components/ui/Select.vue';
import { useI18n } from 'vue-i18n';

defineProps<{
    modelValue: string;
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();

const { t } = useI18n();
</script>

<template>
    <Select
        :model-value="modelValue"
        class="!w-36"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <option value="">{{ t('common.allStatuses') }}</option>
        <option value="active">{{ t('common.active') }}</option>
        <option value="inactive">{{ t('common.inactive') }}</option>
    </Select>
</template>
