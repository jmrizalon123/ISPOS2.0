import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import { useI18n } from 'vue-i18n';

export function useFormPageTitle(entityKey: string, isEdit: MaybeRefOrGetter<boolean>) {
    const { t } = useI18n();

    return computed(() => {
        const entity = t(`entities.${entityKey}`);

        return toValue(isEdit)
            ? t('common.editEntity', { entity })
            : t('common.newEntity', { entity });
    });
}
