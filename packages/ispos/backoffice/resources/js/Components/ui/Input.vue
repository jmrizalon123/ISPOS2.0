<script setup lang="ts">
defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    id?: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <input
        :id="id"
        :type="type ?? 'text'"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        class="block h-10 w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink shadow-sm transition placeholder:text-ink-muted/70 ui-field-focus hover:border-zinc-300 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:border-zinc-600"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
</template>
