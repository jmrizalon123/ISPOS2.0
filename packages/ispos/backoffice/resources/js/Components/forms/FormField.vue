<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    label?: string;
    labelKey?: string;
    hint?: string;
    hintKey?: string;
    error?: string;
    required?: boolean;
    htmlFor?: string;
}>();

const { t } = useI18n();

const resolvedLabel = computed(() => props.label ?? (props.labelKey ? t(`fields.${props.labelKey}`) : ''));
const resolvedHint = computed(() => props.hint ?? (props.hintKey ? t(`hints.${props.hintKey}`) : ''));
</script>

<template>
    <div class="form-field">
        <label v-if="resolvedLabel" :for="htmlFor" class="form-label">
            {{ resolvedLabel }}
            <span v-if="required" class="text-accent" aria-hidden="true">*</span>
        </label>
        <p v-if="resolvedHint" class="form-hint">{{ resolvedHint }}</p>
        <div class="form-control">
            <slot />
        </div>
        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>
