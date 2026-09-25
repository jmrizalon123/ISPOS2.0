import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useLocale() {
    const { t } = useI18n();

    const field = (key: string) => t(`fields.${key}`);
    const column = (key: string) => t(`columns.${key}`);
    const placeholder = (key: string) => t(`placeholders.${key}`);
    const hint = (key: string) => t(`hints.${key}`);
    const filter = (key: string) => t(`filters.${key}`);
    const stat = (key: string) => t(`stats.${key}`);
    const section = (key: string) => t(`forms.sections.${key}`);
    const sectionDesc = (key: string) => t(`forms.sectionDesc.${key}`);
    const tab = (key: string) => t(`forms.tabs.${key}`);
    const tabDesc = (key: string) => t(`forms.tabDesc.${key}`);
    const subtitle = (key: string) => t(`forms.subtitles.${key}`);
    const submit = (key: string) => t(`forms.submit.${key}`);
    const emptyAction = (key: string) => t(`forms.emptyAction.${key}`);
    const toggle = (key: string) => t(`toggles.${key}`);
    const toggleHint = (key: string) => t(`toggles.${key}Hint`);
    const stockStatus = (key: string) => t(`stockStatus.${key}`);
    const card = (key: string) => t(`cards.${key}`);
    const appearanceUi = (key: string) => t(`appearanceUi.${key}`);
    const link = (key: string) => t(`forms.links.${key}`);

    return {
        t,
        field,
        column,
        placeholder,
        hint,
        filter,
        stat,
        section,
        sectionDesc,
        tab,
        tabDesc,
        subtitle,
        submit,
        emptyAction,
        toggle,
        toggleHint,
        stockStatus,
        card,
        appearanceUi,
        link,
    };
}

export function useColumnLabels(keys: string[], extra?: Record<string, { class?: string }>) {
    const { column } = useLocale();

    return computed(() =>
        keys.map((key) => ({
            key,
            label: column(key),
            ...(extra?.[key] ?? {}),
        })),
    );
}
