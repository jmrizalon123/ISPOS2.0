import { nextTick, onBeforeUnmount, onMounted, type Ref } from 'vue';

/** Survives Sidebar remounts during Inertia navigations. */
let persistedScrollTop = 0;

export function useSidebarNavScroll(navRef: Ref<HTMLElement | null>) {
    function persistScroll() {
        if (navRef.value) {
            persistedScrollTop = navRef.value.scrollTop;
        }
    }

    function restoreScroll() {
        nextTick(() => {
            if (navRef.value && persistedScrollTop > 0) {
                navRef.value.scrollTop = persistedScrollTop;
            }
        });
    }

    onMounted(() => {
        restoreScroll();
    });

    onBeforeUnmount(() => {
        persistScroll();
    });

    return {
        persistScroll,
        restoreScroll,
        onNavScroll: persistScroll,
    };
}
