import { nextTick, onMounted, onUnmounted, watch, type Ref, type WatchSource } from 'vue';

const SELECTOR = '[data-reveal]';
const STYLE_ID = 'ispos-scroll-reveal-css';

const REVEAL_CSS = `
[data-reveal] {
  opacity: 0;
  transform: translate3d(0, 1.15rem, 0);
  transition:
    opacity 0.65s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
  transition-delay: var(--reveal-delay, 0ms);
  will-change: opacity, transform;
}
[data-reveal].is-inview {
  opacity: 1;
  transform: translate3d(0, 0, 0);
}
@media (prefers-reduced-motion: reduce) {
  [data-reveal] {
    opacity: 1 !important;
    transform: none !important;
    transition: none !important;
  }
}
`;

function ensureStyles(): void {
    if (typeof document === 'undefined' || document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = REVEAL_CSS;
    document.head.appendChild(style);
}

function prefersReducedMotion(): boolean {
    return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function useScrollReveal(options?: {
    root?: Ref<HTMLElement | null>;
    deps?: WatchSource | WatchSource[];
}) {
    let observer: IntersectionObserver | null = null;

    function resolveRoot(): ParentNode {
        return options?.root?.value ?? document;
    }

    function revealAll(root: ParentNode): void {
        root.querySelectorAll(SELECTOR).forEach((el) => el.classList.add('is-inview'));
    }

    function observe(root: ParentNode): void {
        ensureStyles();
        if (prefersReducedMotion()) {
            revealAll(root);
            return;
        }
        if (!observer) {
            observer = new IntersectionObserver(
                (entries) => {
                    for (const entry of entries) {
                        if (!entry.isIntersecting) continue;
                        entry.target.classList.add('is-inview');
                        observer?.unobserve(entry.target);
                    }
                },
                { root: null, rootMargin: '0px 0px -6% 0px', threshold: 0.08 },
            );
        }
        root.querySelectorAll(SELECTOR).forEach((el) => {
            if (el.classList.contains('is-inview')) return;
            observer!.observe(el);
        });
    }

    async function refresh(): Promise<void> {
        await nextTick();
        observe(resolveRoot());
    }

    onMounted(() => {
        void refresh();
    });

    if (options?.deps) {
        watch(options.deps, () => {
            void refresh();
        }, { flush: 'post' });
    }

    onUnmounted(() => {
        observer?.disconnect();
        observer = null;
    });

    return { refresh };
}
