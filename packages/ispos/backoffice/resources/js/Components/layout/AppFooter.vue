<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const { t } = useI18n();
const year = new Date().getFullYear();
const appName = computed(() => page.props.app?.name ?? 'iSPOS');
</script>

<template>
    <footer class="app-main-footer shrink-0 border-t border-line bg-surface/90 backdrop-blur-md">
        <div class="app-page flex flex-col items-center justify-between gap-2 py-3 text-xs text-ink-muted sm:flex-row">
            <p>
                © {{ year }} {{ appName }} · Enterprise POS Foundation
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                <Link :href="route('appearance.edit')" class="transition hover:text-accent">{{ t('common.appearance') }}</Link>
                <Link :href="route('profile.edit')" class="transition hover:text-accent">{{ t('common.profile') }}</Link>
                <span class="hidden sm:inline">·</span>
                <span class="font-mono text-[10px] uppercase tracking-wider opacity-70">Phase 1</span>
            </div>
        </div>
    </footer>
</template>
