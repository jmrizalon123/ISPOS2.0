<script setup lang="ts">
defineProps<{
    modelValue?: string | number;
    disabled?: boolean;
    id?: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        class="block h-10 w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink shadow-sm transition ui-field-focus hover:border-zinc-300 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:border-zinc-600"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
        <slot />
    </select>
</template>
