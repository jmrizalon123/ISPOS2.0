<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    backHref?: string;
    backLabel?: string;
    backLabelKey?: string;
    eyebrow?: string;
}>();

const { t } = useI18n();

const resolvedBackLabel = computed(
    () => props.backLabel ?? (props.backLabelKey ? t(`forms.back.${props.backLabelKey}`) : t('common.back')),
);
</script>

<template>
    <div v-if="backHref || eyebrow" class="page-back-row">
        <Link v-if="backHref" :href="backHref" class="page-back-link group">
            <span class="page-back-link__icon" aria-hidden="true">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </span>
            <span class="page-back-link__label">{{ resolvedBackLabel }}</span>
        </Link>
        <span v-if="eyebrow" class="page-back-eyebrow">{{ eyebrow }}</span>
    </div>
</template>
