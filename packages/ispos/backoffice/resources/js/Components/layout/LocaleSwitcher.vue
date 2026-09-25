<script setup lang="ts">
import Dropdown from '@/Components/ui/Dropdown.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import { useGoogleTranslate } from '@/Composables/useGoogleTranslate';
import { useTranslation } from '@/Composables/useTranslation';
import { computed, onMounted, ref, watch } from 'vue';

const { currentLocale, availableLocales, switchLocale, usesGoogleWidget } = useTranslation();
const { activeLocale, ensureLoaded, switchLanguage, switching } = useGoogleTranslate();

const selected = ref<string>(currentLocale.value);

const displayLocale = computed(() => (
    usesGoogleWidget.value ? activeLocale.value : currentLocale.value
));

const currentLabel = computed(() => {
    const match = availableLocales.value.find((item) => item.code === displayLocale.value);

    return match?.native ?? displayLocale.value;
});

watch(displayLocale, (value) => {
    selected.value = value;
});

watch(currentLocale, (value) => {
    if (!usesGoogleWidget.value) {
        selected.value = value;
    }
});

onMounted(() => {
    if (usesGoogleWidget.value) {
        void ensureLoaded().then(() => {
            selected.value = activeLocale.value;
        });
    }
});

async function selectLocale(code: string) {
    if (code === displayLocale.value || switching.value) {
        return;
    }

    if (usesGoogleWidget.value) {
        selected.value = code;

        try {
            await switchLanguage(code);
        } catch {
            selected.value = displayLocale.value;
        }

        return;
    }

    selected.value = code;
    switchLocale(code);
}
</script>

<template>
    <Dropdown align="right" width="44" content-classes="py-1 bg-surface">
        <template #trigger>
            <button
                type="button"
                class="topbar-icon-btn topbar-icon-btn--locale"
                :disabled="switching"
                :aria-label="$t('common.language') + ': ' + currentLabel"
                :title="currentLabel"
            >
                <NavIcon name="globe" subtle />
            </button>
        </template>

        <template #content>
            <button
                v-for="item in availableLocales"
                :key="item.code"
                type="button"
                class="language-switcher__option"
                :class="{ 'language-switcher__option--active': item.code === displayLocale }"
                @click="selectLocale(item.code)"
            >
                <span>{{ item.native }}</span>
                <span v-if="item.code === displayLocale" class="language-switcher__check">✓</span>
            </button>
        </template>
    </Dropdown>
</template>
