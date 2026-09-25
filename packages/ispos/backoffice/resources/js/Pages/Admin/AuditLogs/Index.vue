<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('auditLogs');
const { t } = useI18n();

const lineCol = useLineTableColumn();
const extraCols = useColumns([
    { key: 'created_at', col: 'when' },
    { key: 'user' },
    { key: 'action' },
    { key: 'module' },
    { key: 'record_id', col: 'record' },
    { key: 'ip_address', col: 'ip' },
]);

const props = defineProps<{
    logs: Paginated<{
        id: string;
        action: string;
        module: string;
        record_type: string | null;
        record_id: string | null;
        ip_address: string | null;
        created_at: string;
        user?: { id: string; name: string; email: string } | null;
    }>;
    filters: { search: string; module: string };
    modules: string[];
}>();

const search = ref(props.filters.search ?? '');
const moduleFilter = ref(props.filters.module ?? '');
const filterQuery = computed(() => ({
    search: search.value || undefined,
    module: moduleFilter.value || undefined,
}));
const hasActiveFilters = computed(() => !!search.value || !!moduleFilter.value);
const countLabel = useRecordCountLabel(() => props.logs.total, 'entry');

watch([search, moduleFilter], () => {
    router.get(
        route('admin.audit-logs.index'),
        { search: search.value || undefined, module: moduleFilter.value || undefined },
        { preserveState: true, replace: true },
    );
});

function clearFilters() {
    search.value = '';
    moduleFilter.value = '';
}

const columns = computed(() => [lineCol.value, ...extraCols.value]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="logs.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-model="moduleFilter" class="!w-44">
                    <option value="">All modules</option>
                    <option v-for="moduleName in modules" :key="moduleName" :value="moduleName">{{ moduleName }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState v-if="!logs.data.length" :title="page.emptyTitle" :description="page.emptyDescription" />

            <DataTable v-else :columns="columns" :rows="logs.data" :paginated="logs" pagination-route="admin.audit-logs.index" :pagination-query="filterQuery" viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(logs.from, index) }}</span>
                </template>
                <template #cell-created_at="{ row }">
                    {{ new Date(String(row.created_at)).toLocaleString() }}
                </template>
                <template #cell-user="{ row }">
                    {{ row.user?.name ?? 'System' }}
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
