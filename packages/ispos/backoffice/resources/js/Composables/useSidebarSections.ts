import { navSections } from '@/config/navigation';
import { ref } from 'vue';

const STORAGE_KEY = 'ispos-sidebar-open-sections';

function readStoredSections(): Set<string> | null {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as unknown;

        if (!Array.isArray(parsed)) {
            return null;
        }

        return new Set(parsed.filter((id): id is string => typeof id === 'string'));
    } catch {
        return null;
    }
}

function writeStoredSections(sections: Set<string>) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...sections]));
}

/** Shared across Sidebar remounts so expanded modules stay open. */
const openSections = ref<Set<string>>(readStoredSections() ?? new Set<string>());
let initialized = false;

export function useSidebarSections(isActive: (match: string) => boolean) {
    function isSectionOpen(sectionId: string, searching: boolean): boolean {
        if (searching) {
            return true;
        }

        return openSections.value.has(sectionId);
    }

    function sectionHasActiveItem(sectionId: string, sectionItemMatches: string[]): boolean {
        return sectionItemMatches.some((match) => isActive(match));
    }

    function ensureInitialSections(sectionItems: Record<string, string[]>) {
        if (initialized) {
            return;
        }

        initialized = true;

        if (openSections.value.size > 0) {
            return;
        }

        const initial = new Set<string>();

        for (const section of navSections) {
            const matches = sectionItems[section.id] ?? [];

            if (sectionHasActiveItem(section.id, matches)) {
                initial.add(section.id);
            }
        }

        if (initial.size === 0) {
            initial.add(navSections[0]?.id ?? 'overview');
        }

        openSections.value = initial;
        writeStoredSections(initial);
    }

    function openSection(sectionId: string) {
        if (openSections.value.has(sectionId)) {
            return;
        }

        const next = new Set(openSections.value);
        next.add(sectionId);
        openSections.value = next;
        writeStoredSections(next);
    }

    function toggleSection(sectionId: string) {
        const next = new Set(openSections.value);

        if (next.has(sectionId)) {
            next.delete(sectionId);
        } else {
            next.add(sectionId);
        }

        openSections.value = next;
        writeStoredSections(next);
    }

    return {
        openSections,
        isSectionOpen,
        sectionHasActiveItem,
        ensureInitialSections,
        openSection,
        toggleSection,
    };
}
