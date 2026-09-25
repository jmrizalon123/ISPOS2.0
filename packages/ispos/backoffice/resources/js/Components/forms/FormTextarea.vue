<script setup lang="ts">
defineProps<{
    modelValue?: string;
    rows?: number;
    placeholder?: string;
    disabled?: boolean;
    id?: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <textarea
        :id="id"
        :rows="rows ?? 3"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        class="form-textarea"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
</template>
