import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import { useI18n } from 'vue-i18n';

export function useRecordCountLabel(
    total: MaybeRefOrGetter<number>,
    entityKey: string,
    pluralEntityKey?: string,
) {
    const { t } = useI18n();

    return computed(() => {
        const count = toValue(total);
        const word = count === 1
            ? t(`entities.${entityKey}`)
            : t(`entities.${pluralEntityKey ?? `${entityKey}s`}`);

        return `${count.toLocaleString()} ${word}`;
    });
}
