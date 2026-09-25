<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const model = defineModel<boolean>({ required: true });

const props = defineProps<{
    label?: string;
    labelKey?: string;
    hint?: string;
    hintKey?: string;
    disabled?: boolean;
}>();

const { t } = useI18n();

const resolvedLabel = computed(() => props.label ?? (props.labelKey ? t(`toggles.${props.labelKey}`) : ''));
const resolvedHint = computed(() => {
    if (props.hint) return props.hint;
    if (props.hintKey) return t(`toggles.${props.hintKey}Hint`);
    if (props.labelKey) return t(`toggles.${props.labelKey}Hint`, '');
    return '';
});
</script>

<template>
    <label class="form-toggle" :class="{ 'form-toggle--on': model, 'form-toggle--disabled': props.disabled }">
        <input v-model="model" type="checkbox" class="sr-only" :disabled="props.disabled" />
        <span class="form-toggle-track" aria-hidden="true">
            <span class="form-toggle-thumb" />
        </span>
        <span class="form-toggle-text">
            <span class="form-toggle-label">{{ resolvedLabel }}</span>
            <span v-if="resolvedHint" class="form-toggle-hint">{{ resolvedHint }}</span>
        </span>
    </label>
</template>
