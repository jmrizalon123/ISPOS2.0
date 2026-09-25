<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    cancelHref: string;
    submitLabel?: string;
    processing?: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="form-action-bar">
        <p class="form-action-hint hidden text-xs text-ink-muted sm:block">
            {{ t('common.formSaveHint', { action: submitLabel ?? t('common.save') }) }}
        </p>
        <div class="form-action-buttons">
            <Link :href="cancelHref">
                <Button type="button" variant="secondary">{{ t('common.cancel') }}</Button>
            </Link>
            <Button type="submit" :disabled="processing">
                {{ processing ? t('common.saving') : (submitLabel ?? t('common.save')) }}
            </Button>
        </div>
    </div>
</template>
