<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: string;
        contentClasses?: string;
        closeOnContentClick?: boolean;
        /** On small screens, anchor below trigger (default) or open as a bottom sheet. */
        mobileMode?: 'anchored' | 'sheet';
    }>(),
    {
        align: 'right',
        width: '48',
        contentClasses: 'py-1 bg-surface',
        closeOnContentClick: true,
        mobileMode: 'anchored',
    },
);

const open = defineModel<boolean>('open', { default: false });

const triggerEl = ref<HTMLElement | null>(null);
const panelEl = ref<HTMLElement | null>(null);
const panelStyle = ref<Record<string, string>>({});
const isSheetMode = ref(false);

const MOBILE_BREAKPOINT = 640;
const VIEWPORT_MARGIN = 12;

/** Tailwind cannot compile `w-${width}` at build time, so resolve the spacing scale to a real width. */
const widthStyle = computed(() => {
    if (isSheetMode.value) {
        return undefined;
    }

    const steps = Number(props.width);

    return Number.isFinite(steps) ? { width: `${steps / 4}rem` } : undefined;
});

const widthClass = computed(() => {
    if (isSheetMode.value) {
        return '';
    }

    return Number.isFinite(Number(props.width)) ? '' : `w-${props.width}`;
});

const panelClasses = computed(() => [
    widthClass.value,
    props.contentClasses,
    isSheetMode.value
        ? 'origin-bottom rounded-t-2xl rounded-b-xl sm:origin-top-right sm:rounded-2xl'
        : props.align === 'right'
          ? 'origin-top-right'
          : 'origin-top-left',
]);

function closeOnEscape(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        open.value = false;
    }
}

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function resolvePanelWidth(panel: HTMLElement | null): number {
    if (panel?.offsetWidth) {
        return panel.offsetWidth;
    }

    const steps = Number(props.width);

    if (Number.isFinite(steps)) {
        return (steps / 4) * 16;
    }

    return 192;
}

function updatePanelPosition() {
    const trigger = triggerEl.value;
    const panel = panelEl.value;

    if (!trigger) {
        return;
    }

    const viewportWidth = window.innerWidth;
    const isMobile = viewportWidth < MOBILE_BREAKPOINT;
    isSheetMode.value = isMobile && props.mobileMode === 'sheet';

    if (isSheetMode.value) {
        panelStyle.value = {
            top: 'auto',
            bottom: `${VIEWPORT_MARGIN}px`,
            left: `${VIEWPORT_MARGIN}px`,
            right: `${VIEWPORT_MARGIN}px`,
            width: 'auto',
            maxHeight: `min(88vh, calc(100dvh - ${VIEWPORT_MARGIN * 2}px - env(safe-area-inset-bottom, 0px)))`,
        };

        return;
    }

    const rect = trigger.getBoundingClientRect();
    const gap = 8;
    const panelWidth = resolvePanelWidth(panel);

    let left = props.align === 'right' ? rect.right - panelWidth : rect.left;
    left = Math.max(VIEWPORT_MARGIN, Math.min(left, viewportWidth - panelWidth - VIEWPORT_MARGIN));

    panelStyle.value = {
        top: `${rect.bottom + gap}px`,
        left: `${left}px`,
        right: 'auto',
        bottom: 'auto',
        maxHeight: '',
    };
}

function handleClickOutside(event: MouseEvent) {
    if (!open.value) {
        return;
    }

    const target = event.target as Node;

    if (triggerEl.value?.contains(target) || panelEl.value?.contains(target)) {
        return;
    }

    close();
}

function bindGlobalListeners() {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('resize', updatePanelPosition);
    window.addEventListener('scroll', updatePanelPosition, true);
}

function unbindGlobalListeners() {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('mousedown', handleClickOutside);
    window.removeEventListener('resize', updatePanelPosition);
    window.removeEventListener('scroll', updatePanelPosition, true);
}

watch(open, (isOpen) => {
    const onMobile = window.innerWidth < MOBILE_BREAKPOINT;

    document.body.classList.toggle('overflow-hidden', isOpen && onMobile);

    if (isOpen) {
        isSheetMode.value = onMobile && props.mobileMode === 'sheet';

        void nextTick(() => {
            updatePanelPosition();
            void nextTick(() => updatePanelPosition());
            bindGlobalListeners();
        });

        return;
    }

    isSheetMode.value = false;
    unbindGlobalListeners();
});

onUnmounted(() => {
    unbindGlobalListeners();
    document.body.classList.remove('overflow-hidden');
});

function onPanelClick() {
    if (props.closeOnContentClick) {
        close();
    }
}
</script>

<template>
    <div class="relative">
        <div ref="triggerEl" @click.stop="toggle">
            <slot name="trigger" :open="open" />
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="open"
                    class="fixed inset-0 z-[120] bg-zinc-950/25 backdrop-blur-[1px] sm:bg-zinc-950/10 sm:backdrop-blur-none"
                    aria-hidden="true"
                    @mousedown="close"
                />
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                :enter-from-class="isSheetMode ? 'opacity-0 translate-y-6' : 'opacity-0 translate-y-1 scale-[0.98]'"
                :enter-to-class="isSheetMode ? 'opacity-100 translate-y-0' : 'opacity-100 translate-y-0 scale-100'"
                leave-active-class="transition duration-150 ease-in"
                :leave-from-class="isSheetMode ? 'opacity-100 translate-y-0' : 'opacity-100 translate-y-0 scale-100'"
                :leave-to-class="isSheetMode ? 'opacity-0 translate-y-4' : 'opacity-0 translate-y-1 scale-[0.98]'"
            >
                <div
                    v-show="open"
                    ref="panelEl"
                    class="fixed z-[121] flex max-w-[calc(100vw-1.5rem)] flex-col border border-line/80 bg-surface shadow-panel sm:max-w-[calc(100vw-1.5rem)]"
                    :class="[panelClasses, !isSheetMode ? 'rounded-2xl' : '']"
                    :style="{ ...widthStyle, ...panelStyle }"
                    @mousedown.stop
                    @click="onPanelClick"
                >
                    <slot name="content" :close="close" :sheet="isSheetMode" />
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
