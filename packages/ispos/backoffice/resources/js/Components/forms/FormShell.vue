<script setup lang="ts">
import PageBackRow from '@/Components/ui/PageBackRow.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    title: string;
    subtitle?: string;
    subtitleKey?: string;
    eyebrow?: string;
    backHref?: string;
    backLabel?: string;
    backLabelKey?: string;
    sectionLabel?: string;
    sectionDescription?: string;
}>();

const { t } = useI18n();

const resolvedSubtitle = computed(() => props.subtitle ?? (props.subtitleKey ? t(`forms.subtitles.${props.subtitleKey}`) : ''));
</script>

<template>
    <div class="form-page form-page--fill">
        <div class="form-card">
            <header class="form-card-header">
                <div class="form-card-header-main">
                    <PageBackRow
                        :back-href="backHref"
                        :back-label="backLabel"
                        :back-label-key="backLabelKey"
                        :eyebrow="eyebrow"
                    />
                    <h2 class="form-card-title">{{ title }}</h2>
                    <p v-if="resolvedSubtitle" class="form-card-subtitle">{{ resolvedSubtitle }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-start justify-end gap-3">
                    <div v-if="sectionLabel" class="form-card-header-section">
                        <p class="form-card-section-label">{{ sectionLabel }}</p>
                        <p v-if="sectionDescription" class="form-card-section-desc">{{ sectionDescription }}</p>
                    </div>
                    <div v-if="$slots['header-actions']" class="form-card-header-actions">
                        <slot name="header-actions" />
                    </div>
                </div>
            </header>

            <div class="form-card-body">
                <slot />
            </div>
        </div>
    </div>
</template>
