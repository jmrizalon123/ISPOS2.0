<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    title?: string;
    titleKey?: string;
    description?: string;
    descriptionKey?: string;
}>();

const { t } = useI18n();

const resolvedTitle = computed(() => props.title ?? (props.titleKey ? t(`forms.sections.${props.titleKey}`) : ''));
const resolvedDescription = computed(() =>
    props.description ?? (props.descriptionKey ? t(`forms.sectionDesc.${props.descriptionKey}`) : ''),
);
</script>

<template>
    <section class="form-section">
        <header v-if="resolvedTitle || resolvedDescription" class="form-section-header">
            <h4 v-if="resolvedTitle" class="form-section-title">{{ resolvedTitle }}</h4>
            <p v-if="resolvedDescription" class="form-section-desc">{{ resolvedDescription }}</p>
        </header>
        <div class="form-section-body">
            <slot />
        </div>
    </section>
</template>
