<script setup lang="ts">
import NavIcon from '@/Components/ui/NavIcon.vue';
import { useCommandPalette } from '@/Composables/useCommandPalette';
import { useNavigation } from '@/Composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const { open, closePalette } = useCommandPalette();
const query = ref('');
const { paletteItems, isActive } = useNavigation(query);

watch(open, (isOpen) => {
    if (!isOpen) {
        query.value = '';
    }
});

function onKey(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open.value = !open.value;
    }

    if (e.key === 'Escape') {
        closePalette();
    }
}

function onSelect() {
    closePalette();
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-start justify-center bg-zinc-950/45 p-4 pt-[10vh] backdrop-blur-sm"
            @click.self="closePalette"
        >
            <div
                class="w-full max-w-xl overflow-hidden rounded-2xl border border-line bg-surface shadow-soft ring-1 ring-black/5 dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                :aria-label="t('common.moduleSearch')"
            >
                <div class="relative border-b border-line">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ink-muted">
                        <NavIcon name="search" />
                    </span>
                    <input
                        v-model="query"
                        type="search"
                        autofocus
                        :placeholder="t('common.searchModules')"
                        class="w-full border-0 bg-transparent py-3.5 pl-11 pr-4 text-sm text-ink placeholder:text-ink-muted focus:ring-0"
                    />
                </div>

                <ul v-if="paletteItems.length" class="max-h-80 overflow-y-auto p-2">
                    <li v-for="item in paletteItems" :key="`${item.route}-${item.label}`">
                        <a
                            v-if="item.opensInNewTab"
                            :href="item.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-surface-muted"
                            @click="onSelect"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-muted text-ink-muted">
                                <NavIcon :name="item.icon" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink">{{ item.label }}</span>
                                <span class="block truncate text-xs capitalize text-ink-muted">{{ item.section }}</span>
                            </span>
                            <span class="text-xs font-medium text-accent">Open app</span>
                        </a>
                        <Link
                            v-else
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-surface-muted"
                            :class="isActive(item.match) ? 'bg-accent-soft/70' : ''"
                            @click="onSelect"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                :class="isActive(item.match) ? 'bg-accent text-white' : 'bg-surface-muted text-ink-muted'"
                            >
                                <NavIcon :name="item.icon" :active="isActive(item.match)" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink">{{ item.label }}</span>
                                <span class="block truncate text-xs capitalize text-ink-muted">{{ item.section }}</span>
                            </span>
                            <span v-if="isActive(item.match)" class="text-xs font-medium text-accent">Current</span>
                        </Link>
                    </li>
                </ul>

                <div v-else class="px-4 py-10 text-center">
                    <p class="text-sm font-medium text-ink">No modules found</p>
                    <p class="mt-1 text-xs text-ink-muted">Try searching by module name or keyword</p>
                </div>

                <div class="flex items-center justify-between border-t border-line px-4 py-2.5 text-xs text-ink-muted">
                    <span>Navigate modules quickly</span>
                    <span>
                        <kbd class="rounded border border-line bg-surface-muted px-1.5 py-0.5 font-mono text-[10px]">Esc</kbd>
                        to close
                    </span>
                </div>
            </div>
        </div>
    </Teleport>
</template>
