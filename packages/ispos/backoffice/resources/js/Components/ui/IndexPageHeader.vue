<script setup lang="ts">
import PageBackRow from '@/Components/ui/PageBackRow.vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        eyebrow?: string;
        backHref?: string;
        backLabel?: string;
        backLabelKey?: string;
        /** Single-row header with bottom border (form toolbars). */
        layout?: 'stack' | 'inline';
    }>(),
    {
        layout: 'stack',
    },
);
</script>

<template>
    <section
        class="index-page-header"
        :class="layout === 'inline' ? 'index-page-header--inline' : 'index-page-header--compact'"
    >
        <div
            class="flex gap-2"
            :class="
                layout === 'inline'
                    ? 'flex-row flex-wrap items-center justify-between'
                    : 'flex-col sm:flex-row sm:items-start sm:justify-between'
            "
        >
            <div
                class="min-w-0 flex-1"
                :class="layout === 'inline' ? 'flex flex-wrap items-center gap-x-3 gap-y-1.5' : ''"
            >
                <PageBackRow
                    :back-href="backHref"
                    :back-label="backLabel"
                    :back-label-key="backLabelKey"
                    :eyebrow="eyebrow"
                />
                <div
                    :class="
                        layout === 'inline'
                            ? 'flex min-w-0 flex-wrap items-baseline gap-x-2.5 gap-y-0.5'
                            : 'min-w-0'
                    "
                >
                    <h2
                        class="font-display font-bold tracking-tight text-ink"
                        :class="layout === 'inline' ? 'text-base sm:text-lg' : 'text-lg sm:text-xl'"
                    >
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="max-w-2xl leading-snug text-ink-muted"
                        :class="layout === 'inline' ? 'mt-0 text-xs sm:text-[13px]' : 'mt-0.5 text-[13px]'"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>
            <div
                v-if="$slots.meta || $slots.actions"
                class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end"
            >
                <slot name="meta" />
                <slot name="actions" />
            </div>
        </div>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </section>
</template>
