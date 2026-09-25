<script setup lang="ts">
import Input from '@/Components/ui/Input.vue';
import { hexToRgb, rgbToHex, type Rgb } from '@/Composables/colorUtils';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    label?: string;
    description?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const hexInput = ref(normalizeHex(props.modelValue));

function normalizeHex(value: string): string {
    const v = value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(v)) {
        return v.toLowerCase();
    }
    if (/^[0-9a-fA-F]{6}$/.test(v)) {
        return `#${v.toLowerCase()}`;
    }
    return '#0f766e';
}

const pickerValue = computed({
    get: () => normalizeHex(props.modelValue),
    set: (value: string) => emit('update:modelValue', normalizeHex(value)),
});

watch(
    () => props.modelValue,
    (value) => {
        hexInput.value = normalizeHex(value);
    },
);

function onHexInput(value: string) {
    hexInput.value = value;
    const normalized = normalizeHex(value.startsWith('#') ? value : `#${value}`);
    if (hexToRgb(normalized)) {
        emit('update:modelValue', normalized);
    }
}

function setFromRgb([r, g, b]: Rgb) {
    emit('update:modelValue', rgbToHex([r, g, b]));
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="label || description">
            <div v-if="label" class="ui-label">{{ label }}</div>
            <p v-if="description" class="text-xs text-ink-muted">{{ description }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label
                class="relative flex h-12 w-12 shrink-0 cursor-pointer overflow-hidden rounded-xl border border-line shadow-sm transition hover:scale-105"
                :style="{ backgroundColor: pickerValue }"
            >
                <input
                    type="color"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    :value="pickerValue"
                    @input="pickerValue = ($event.target as HTMLInputElement).value"
                />
            </label>

            <div class="flex min-w-0 flex-1 items-center gap-2">
                <Input
                    :model-value="hexInput"
                    class="font-mono uppercase"
                    placeholder="#0f766e"
                    @update:model-value="onHexInput"
                />
                <span
                    class="hidden h-10 w-10 shrink-0 rounded-lg border border-line sm:block"
                    :style="{ backgroundColor: pickerValue }"
                />
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <button
                v-for="swatch in ['#0f766e', '#2563eb', '#7c3aed', '#e11d48', '#d97706', '#475569', '#059669', '#4f46e5']"
                :key="swatch"
                type="button"
                class="h-7 w-7 rounded-md border border-line shadow-sm transition hover:scale-110"
                :class="pickerValue === swatch ? 'ring-2 ring-accent ring-offset-1' : ''"
                :style="{ backgroundColor: swatch }"
                :title="swatch"
                @click="setFromRgb(hexToRgb(swatch)!)"
            />
        </div>
    </div>
</template>
