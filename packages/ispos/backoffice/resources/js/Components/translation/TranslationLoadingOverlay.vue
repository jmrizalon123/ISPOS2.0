<script setup lang="ts">
import { useGoogleTranslate } from '@/Composables/useGoogleTranslate';
import { useTranslation } from '@/Composables/useTranslation';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { switching } = useGoogleTranslate();
const { usesGoogleWidget } = useTranslation();
const { t } = useI18n();

const visible = computed(() => usesGoogleWidget.value && switching.value);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="visible"
                class="translation-loading-overlay"
                role="alertdialog"
                aria-live="assertive"
                aria-busy="true"
                :aria-label="t('common.translating')"
            >
                <div class="translation-loading-overlay__panel">
                    <div class="translation-loading-overlay__spinner" aria-hidden="true" />
                    <p class="translation-loading-overlay__message">{{ t('common.translating') }}</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
