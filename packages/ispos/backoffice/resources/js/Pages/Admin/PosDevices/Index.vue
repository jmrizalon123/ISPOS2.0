<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import StatusFilterSelect from '@/Components/ui/StatusFilterSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import { confirmAction } from '@/Composables/useConfirm';
import type { Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('posDevices');
const { t } = useI18n();
const { filter } = useLocale();
const lineCol = useLineTableColumn();

const props = defineProps<{
    devices: Paginated<{
        id: string;
        name: string;
        fingerprint: string | null;
        status: string;
        last_bootstrap_at: string | null;
        last_sync_at: string | null;
        created_at: string;
        store?: { id: string; store_name: string; store_code: string };
        register?: { id: string; register_name: string; register_code: string };
        registered_by_user?: { id: string; name: string } | null;
    }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { search: string; store_id: string; status: string };
}>();

const { search, storeId, status, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.pos-devices.index',
    props.filters,
    ['search', 'store_id', 'status'],
    [],
    props.stores.map((store) => ({ ...store, company_id: '' })),
);

const countLabel = useRecordCountLabel(() => props.devices.total, 'device');

const deviceCols = useColumns([
    { key: 'name', col: 'device' },
    { key: 'store', col: 'storeRegister' },
    { key: 'last_sync_at', col: 'lastSync' },
]);

const columns = computed(() => [
    lineCol.value,
    deviceCols.value[0],
    deviceCols.value[1],
    { key: 'status', label: t('common.status'), minWidth: 96 },
    deviceCols.value[2],
]);

const statusOptions = computed(() => [
    { value: '', label: t('common.allStatuses') },
    { value: 'active', label: t('common.active') },
    { value: 'revoked', label: filter('revoked') },
]);

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

async function revokeDevice(id: string, name: string) {
    const confirmed = await confirmAction({
        title: 'Revoke device?',
        message: `"${name}" will lose offline sync access immediately.`,
        confirmLabel: 'Revoke',
        variant: 'danger',
    });

    if (confirmed) {
        router.post(route('admin.pos-devices.revoke', id));
    }
}

const showStoreFilter = computed(() => props.stores.length > 0);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="devices.data.length ? 'index-page--fill' : undefined">
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

            <IndexToolbar class="shrink-0">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="w-full sm:max-w-xs" />
                <Select v-if="showStoreFilter" v-model="storeId" class="w-full sm:w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.store_name }}
                    </option>
                </Select>
                <StatusFilterSelect v-model="status" :options="statusOptions" />
                <Button v-if="hasActiveFilters" variant="ghost" @click="clearFilters">{{ t('common.clearFilters') }}</Button>
            </IndexToolbar>

            <DataTable
                v-if="devices.data.length"
                :columns="columns"
                :rows="devices.data"
                :paginated="devices"
                pagination-route="admin.pos-devices.index"
                :pagination-query="filterQuery"
                sticky-actions
                viewport-fit
                compact
            >
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(devices.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <div>
                        <p class="font-semibold text-ink">{{ row.name }}</p>
                        <p v-if="row.fingerprint" class="truncate text-xs text-ink-muted">{{ row.fingerprint }}</p>
                    </div>
                </template>
                <template #cell-store="{ row }">
                    <div class="text-sm">
                        <p>{{ row.store?.store_name ?? '—' }}</p>
                        <p class="text-xs text-ink-muted">{{ row.register?.register_name ?? '—' }}</p>
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</Badge>
                </template>
                <template #cell-last_sync_at="{ row }">
                    <span class="text-sm text-ink-muted">{{ formatDate(row.last_sync_at) }}</span>
                </template>
                <template #actions="{ row }">
                    <Button
                        v-if="row.status === 'active'"
                        variant="ghost"
                        class="text-red-600 hover:text-red-700"
                        @click="revokeDevice(row.id, row.name)"
                    >
                        {{ t('common.revoke') }}
                    </Button>
                </template>
            </DataTable>

            <EmptyState
                v-else
                :title="page.emptyTitle"
                :description="page.emptyDescription"
            />
        </div>
    </AppLayout>
</template>
