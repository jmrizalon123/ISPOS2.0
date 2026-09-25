<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        total: number;
        page: number;
        pageSize: number;
        pageSizeOptions?: number[];
    }>(),
    {
        pageSizeOptions: () => [10, 20, 50],
    },
);

const emit = defineEmits<{
    'update:page': [number];
    'update:pageSize': [number];
}>();

const { t } = useI18n();

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.pageSize)));

const rangeFrom = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.pageSize + 1));

const rangeTo = computed(() => Math.min(props.page * props.pageSize, props.total));

function setPage(nextPage: number) {
    const clamped = Math.min(Math.max(1, nextPage), totalPages.value);
    emit('update:page', clamped);
}

function onPageSizeChange(event: Event) {
    const value = Number((event.target as HTMLSelectElement).value);
    emit('update:pageSize', value);
    emit('update:page', 1);
}
</script>

<template>
    <div class="table-pagination">
        <label class="table-pagination__size">
            <span class="table-pagination__label">{{ t('table.pageSize') }}</span>
            <select
                class="table-pagination__select"
                :value="pageSize"
                @change="onPageSizeChange"
            >
                <option v-for="option in pageSizeOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
        </label>

        <span class="table-pagination__range tabular-nums">
            {{ t('table.range', { from: rangeFrom, to: rangeTo, total }) }}
        </span>

        <div class="table-pagination__nav">
            <button
                type="button"
                class="table-pagination__button"
                :disabled="page <= 1"
                :aria-label="t('table.firstPage')"
                @click="setPage(1)"
            >
                «
            </button>
            <button
                type="button"
                class="table-pagination__button"
                :disabled="page <= 1"
                :aria-label="t('table.previousPage')"
                @click="setPage(page - 1)"
            >
                ‹
            </button>
            <span class="table-pagination__page tabular-nums">
                {{ t('table.page', { current: page, total: totalPages }) }}
            </span>
            <button
                type="button"
                class="table-pagination__button"
                :disabled="page >= totalPages"
                :aria-label="t('table.nextPage')"
                @click="setPage(page + 1)"
            >
                ›
            </button>
            <button
                type="button"
                class="table-pagination__button"
                :disabled="page >= totalPages"
                :aria-label="t('table.lastPage')"
                @click="setPage(totalPages)"
            >
                »
            </button>
        </div>
    </div>
</template>
