<script setup lang="ts">
import Toast from '@/Components/ui/Toast.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useStorefrontTheme } from '@/Composables/useStorefrontTheme';
import { computed, onMounted } from 'vue';

const props = defineProps<{
    storeName: string;
    primaryColor?: string;
    accentColor?: string;
    logoUrl?: string | null;
    tagline?: string | null;
    wide?: boolean;
}>();

const { mode: themeMode, themeLabel, colorModeAttrs, cycleTheme } = useStorefrontTheme();
useScrollReveal();

const themeStyle = computed(() => ({
    '--sf-primary': props.primaryColor || '#0f766e',
    '--sf-accent': props.accentColor || '#d97706',
    '--sf-primary-rgb': hexToRgb(props.primaryColor || '#0f766e'),
    '--sf-accent-rgb': hexToRgb(props.accentColor || '#d97706'),
}));

function hexToRgb(hex: string): string {
    const clean = hex.replace('#', '');
    const full = clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean;
    const n = Number.parseInt(full, 16);
    if (Number.isNaN(n)) return '15, 118, 110';
    return `${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}`;
}

onMounted(() => {
    const id = 'sf-fonts';
    if (document.getElementById(id)) return;
    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href =
        'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap';
    document.head.appendChild(link);
});
</script>

<template>
    <div class="sf-root" v-bind="colorModeAttrs" :class="{ 'sf-root--wide': wide }" :style="themeStyle">
        <header class="sf-topbar">
            <div class="sf-topbar__inner">
                <div class="sf-brand">
                    <img v-if="logoUrl" :src="logoUrl" alt="" class="sf-brand__logo" />
                    <span v-else class="sf-brand__mark" aria-hidden="true">{{ storeName.slice(0, 1) }}</span>
                    <span class="sf-brand__text">
                        <span class="sf-brand__name">{{ storeName }}</span>
                        <span v-if="tagline" class="sf-brand__tag">{{ tagline }}</span>
                    </span>
                </div>
                <div class="sf-topbar__actions">
                    <button
                        type="button"
                        class="sf-theme-btn"
                        :title="`Theme: ${themeLabel}`"
                        :aria-label="`Theme: ${themeLabel}`"
                        @click="cycleTheme"
                    >
                        <svg v-if="themeMode === 'dark'" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <svg v-else-if="themeMode === 'system'" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 20h8M12 16v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none">
                            <path d="M21 14.5A8.5 8.5 0 1110.5 3a7 7 0 0010.5 11.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <slot name="header-actions" />
                </div>
            </div>
        </header>

        <main class="sf-shell" data-reveal>
            <slot />
        </main>

        <footer class="sf-footer" data-reveal>
            <p>{{ storeName }} · Online ordering</p>
            <p class="sf-footer__powered">Powered by iSPOS</p>
        </footer>

        <Toast />
    </div>
</template>

<style>
.sf-root {
    --sf-ink: #10231f;
    --sf-muted: #5c6f6a;
    --sf-line: rgba(16, 35, 31, 0.08);
    --sf-panel: #ffffff;
    --sf-soft: #f4f7f6;
    --sf-topbar-bg: rgba(255, 255, 255, 0.82);
    --sf-page-0: #f7faf9;
    --sf-page-1: #eef3f1;
    --sf-page-2: #f8faf9;
    min-height: 100vh;
    color: var(--sf-ink);
    background:
        radial-gradient(ellipse 80% 50% at 0% -10%, rgb(var(--sf-primary-rgb) / 0.12), transparent 55%),
        radial-gradient(ellipse 60% 40% at 100% 0%, rgb(var(--sf-accent-rgb) / 0.1), transparent 50%),
        linear-gradient(180deg, var(--sf-page-0) 0%, var(--sf-page-1) 48%, var(--sf-page-2) 100%);
    font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
    color-scheme: light;
}
.sf-root[data-color-mode='dark'] {
    --sf-ink: #e8f0ee;
    --sf-muted: #9aa8a4;
    --sf-line: rgba(232, 240, 238, 0.1);
    --sf-panel: #151a19;
    --sf-soft: #1b2220;
    --sf-topbar-bg: rgba(12, 16, 15, 0.9);
    --sf-page-0: #0c100f;
    --sf-page-1: #101615;
    --sf-page-2: #0e1312;
    color-scheme: dark;
}

.sf-topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    border-bottom: 1px solid var(--sf-line);
    background: var(--sf-topbar-bg);
    backdrop-filter: blur(14px) saturate(1.2);
}

.sf-topbar__inner {
    margin: 0 auto;
    display: flex;
    max-width: 80rem;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1.25rem;
}

.sf-topbar__actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.sf-theme-btn {
    display: inline-grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    color: var(--sf-ink);
    background: transparent;
}
.sf-theme-btn:hover {
    background: var(--sf-soft);
}
.sf-theme-btn svg {
    width: 1.1rem;
    height: 1.1rem;
}

.sf-root--wide .sf-topbar__inner,
.sf-root--wide .sf-shell,
.sf-root--wide .sf-footer {
    max-width: 88rem;
}

.sf-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    text-decoration: none;
    color: inherit;
}

.sf-brand__logo,
.sf-brand__mark {
    height: 2.6rem;
    width: 2.6rem;
    border-radius: 0.85rem;
    flex-shrink: 0;
}

.sf-brand__logo {
    object-fit: cover;
}

.sf-brand__mark {
    display: grid;
    place-items: center;
    background: rgb(var(--sf-primary-rgb) / 0.12);
    color: var(--sf-primary);
    font-family: Fraunces, Georgia, serif;
    font-weight: 700;
    font-size: 1.15rem;
}

.sf-brand__logo + .sf-brand__mark {
    display: none;
}

.sf-brand__text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.sf-brand__name {
    font-family: Fraunces, Georgia, serif;
    font-size: clamp(1.2rem, 2.4vw, 1.55rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.1;
    color: var(--sf-primary);
}

.sf-brand__tag {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.75rem;
    color: var(--sf-muted);
}

.sf-shell {
    margin: 0 auto;
    width: 100%;
    max-width: 80rem;
    padding: 0 1.25rem 2.5rem;
}

.sf-footer {
    margin: 0 auto;
    max-width: 80rem;
    padding: 1.5rem 1.25rem 2rem;
    text-align: center;
    font-size: 0.8rem;
    color: var(--sf-muted);
}

.sf-footer__powered {
    margin-top: 0.25rem;
    opacity: 0.7;
    font-size: 0.72rem;
}

/* Shared panel surfaces used by checkout / orders / auth */
.sf-root[data-color-mode='dark'] .sf-panel,
.sf-root[data-color-mode='dark'] .sf-orders__card,
.sf-root[data-color-mode='dark'] .sf-orders__empty,
.sf-root[data-color-mode='dark'] .sf-auth,
.sf-root[data-color-mode='dark'] .sf-confirm .sf-panel {
    background: var(--sf-panel);
    border-color: var(--sf-line);
    color: var(--sf-ink);
}
</style>
