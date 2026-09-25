<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import Select from '@/Components/ui/Select.vue';
import KdsLayout from '@/Layouts/KdsLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const { t, field } = useLocale();

const props = defineProps<{
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    context: { store_id: string | null };
}>();

const form = useForm({
    store_id: props.context.store_id ?? props.stores[0]?.id ?? '',
});

function saveSession() {
    form.post(route('kds.session.store'));
}
</script>

<template>
    <Head :title="t('nav.kds_index')" />

    <KdsLayout>
        <div class="flex flex-1 items-center justify-center p-6">
            <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-xl">
                <h1 class="font-display text-2xl font-bold text-white">{{ t('kds.setupTitle') }}</h1>
                <p class="mt-2 text-sm text-zinc-400">{{ t('kds.setupDescription') }}</p>

                <form class="mt-8 space-y-5" @submit.prevent="saveSession">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-zinc-400">{{ t('common.store') }}</label>
                        <Select v-model="form.store_id" :disabled="form.processing">
                            <option value="" disabled>{{ field('selectStore') }}</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                {{ store.store_name }} ({{ store.store_code }})
                            </option>
                        </Select>
                        <p v-if="form.errors.store_id" class="mt-1 text-xs text-red-400">{{ form.errors.store_id }}</p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="!form.store_id || form.processing">
                        {{ t('kds.openBoard') }}
                    </Button>
                </form>
            </div>
        </div>
    </KdsLayout>
</template>
