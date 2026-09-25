<script setup lang="ts">
import TranslationLoadingOverlay from '@/Components/translation/TranslationLoadingOverlay.vue';
import { useGoogleTranslate } from '@/Composables/useGoogleTranslate';
import { hideGoogleTranslateBanner, scheduleGoogleBannerCleanup, startGoogleBannerGuard } from '@/Utils/googleTranslateBanner';
import { onMounted, onUnmounted, watch } from 'vue';

const { isActive, ensureLoaded } = useGoogleTranslate();

let bannerObserver: MutationObserver | null = null;

onMounted(() => {
    if (!isActive.value) {
        return;
    }

    bannerObserver = startGoogleBannerGuard();
    scheduleGoogleBannerCleanup();
    void ensureLoaded().then(() => {
        hideGoogleTranslateBanner();
        scheduleGoogleBannerCleanup();
    });
});

watch(isActive, (active) => {
    if (active) {
        bannerObserver?.disconnect();
        bannerObserver = startGoogleBannerGuard();
        void ensureLoaded().then(hideGoogleTranslateBanner);
    } else {
        bannerObserver?.disconnect();
        bannerObserver = null;
    }
});

onUnmounted(() => {
    bannerObserver?.disconnect();
    bannerObserver = null;
});
</script>

<template>
    <div v-if="isActive" id="google_translate_host" class="google-translate-host" aria-hidden="true" />
    <TranslationLoadingOverlay />
</template>
