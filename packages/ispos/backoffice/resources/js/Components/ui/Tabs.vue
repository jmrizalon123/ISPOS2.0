<script setup lang="ts">
withDefaults(
    defineProps<{
        tabs: Array<{ key: string; label: string; description?: string }>;
        variant?: 'underline' | 'pill';
    }>(),
    { variant: 'underline' },
);

const model = defineModel<string>({ required: true });
</script>

<template>
    <div>
        <div
            v-if="variant === 'pill'"
            class="inline-flex w-full flex-wrap gap-1 rounded-xl border border-line/80 bg-surface-muted/50 p-1 sm:w-auto"
            role="tablist"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="min-w-0 flex-1 rounded-lg px-3 py-2 text-left transition duration-150 sm:flex-none sm:px-4"
                :class="
                    model === tab.key
                        ? 'bg-surface text-ink shadow-sm ring-1 ring-line/60'
                        : 'text-ink-muted hover:bg-surface/70 hover:text-ink'
                "
                :aria-selected="model === tab.key"
                @click="model = tab.key"
            >
                <span class="block truncate text-sm font-semibold">{{ tab.label }}</span>
                <span v-if="tab.description" class="mt-0.5 block truncate text-[11px] font-normal opacity-80">
                    {{ tab.description }}
                </span>
            </button>
        </div>

        <div v-else class="flex gap-1 overflow-x-auto border-b border-line" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="-mb-px shrink-0 border-b-2 px-3 py-2.5 text-sm font-medium transition"
                :class="
                    model === tab.key
                        ? 'border-accent text-accent'
                        : 'border-transparent text-ink-muted hover:text-ink'
                "
                :aria-selected="model === tab.key"
                @click="model = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="pt-5">
            <slot :active="model" />
        </div>
    </div>
</template>
