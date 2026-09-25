<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';

defineProps<{
    tabs: Array<{ key: string; label: string; description?: string }>;
    tabErrors?: Record<string, number>;
}>();

const model = defineModel<string>({ required: true });
const { t } = useLocale();

function badgeLabel(count: number): string {
    return count > 9 ? '9+' : String(count);
}
</script>

<template>
    <div class="form-tabs">
        <aside class="form-tabs-aside">
            <nav class="form-tabs-nav scrollbar-visible" :aria-label="t('messages.formSections')">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="form-tab"
                    :class="{ 'form-tab--active': model === tab.key, 'form-tab--error': (tabErrors?.[tab.key] ?? 0) > 0 }"
                    @click="model = tab.key"
                >
                    <span class="form-tab-header">
                        <span class="form-tab-label">{{ tab.label }}</span>
                        <span
                            v-if="tabErrors?.[tab.key]"
                            class="form-tab-badge"
                            :title="t('messages.tabErrors')"
                        >
                            {{ badgeLabel(tabErrors[tab.key]) }}
                        </span>
                    </span>
                    <span v-if="tab.description" class="form-tab-desc">{{ tab.description }}</span>
                </button>
            </nav>
        </aside>
        <div class="form-tabs-panel">
            <div class="form-tabs-panel-scroll scrollbar-visible">
                <slot :active="model" />
            </div>
        </div>
    </div>
</template>
