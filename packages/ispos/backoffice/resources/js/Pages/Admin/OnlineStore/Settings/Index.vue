<script setup lang="ts">
import { computed } from 'vue';
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCatalogIndexFilters } from '@/Composables/useCatalogIndexFilters';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import { usePermissions } from '@/Composables/usePermissions';
import { paginatedRowNumber, useLineTableColumn } from '@/Composables/useTableColumns';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

interface OnlineStoreSettingRow {
    id: string;
    slug: string;
    storefront_name: string | null;
    is_published: boolean;
    status: string;
    public_url: string;
    store?: { id: string; store_name: string; store_code: string; store_category?: string | null } | null;
    company?: { id: string; name: string; display_name?: string | null } | null;
    updated_at?: string | null;
}

const { can } = usePermissions();
const page = useModulePage('onlineStoreSettings');
const { t } = useI18n();
const lineCol = useLineTableColumn();

const props = defineProps<{
    settings: Paginated<OnlineStoreSettingRow>;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    filters: { search: string; company_id: string; status: string };
}>();

const { search, companyId, status, showCompanyFilter, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.online-store-settings.index',
    { ...props.filters, store_id: '' },
    ['search', 'company_id', 'status'],
    props.companies,
    [],
);

const countLabel = useRecordCountLabel(() => props.settings.total, 'storefront');

const columns = computed(() => [
    lineCol.value,
    { key: 'store', label: t('common.store') },
    { key: 'storefront_name', label: 'Storefront' },
    { key: 'slug', label: 'Slug' },
    { key: 'published', label: 'Published' },
    { key: 'status', label: t('common.status') },
    { key: 'link', label: 'URL' },
]);

function togglePublish(id: string) {
    router.post(route('admin.online-store-settings.publish', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="settings.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader :eyebrow="page.eyebrow" :title="page.title" :description="page.description">
                <template #meta><Badge variant="neutral">{{ countLabel }}</Badge></template>
                <template v-if="can('online_store.manage')" #actions>
                    <Link :href="route('admin.online-store-settings.create')"><Button>{{ page.newButton }}</Button></Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-model="status" class="!w-44">
                    <option value="">{{ t('common.allStatuses') }}</option>
                    <option value="active">{{ t('common.active') }}</option>
                    <option value="inactive">{{ t('common.inactive') }}</option>
                </Select>
            </IndexToolbar>

            <EmptyState
                v-if="!settings.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="can('online_store.manage') ? page.emptyAction : undefined"
                @action="can('online_store.manage') && router.visit(route('admin.online-store-settings.create'))"
            />

            <DataTable
                v-else
                :columns="columns"
                :rows="settings.data"
                :paginated="settings"
                pagination-route="admin.online-store-settings.index"
                :pagination-query="filterQuery"
                sticky-actions
                viewport-fit
                compact
            >
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(settings.from, index) }}</span>
                </template>
                <template #cell-store="{ row }">
                    {{ (row as OnlineStoreSettingRow).store?.store_name ?? '—' }}
                </template>
                <template #cell-storefront_name="{ row }">
                    <Link
                        :href="route('admin.online-store-settings.edit', (row as OnlineStoreSettingRow).id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ (row as OnlineStoreSettingRow).storefront_name || 'Untitled' }}
                    </Link>
                </template>
                <template #cell-slug="{ row }">
                    <code class="text-xs">{{ (row as OnlineStoreSettingRow).slug }}</code>
                </template>
                <template #cell-published="{ row }">
                    <Badge :variant="(row as OnlineStoreSettingRow).is_published ? 'success' : 'neutral'">
                        {{ (row as OnlineStoreSettingRow).is_published ? 'Published' : 'Draft' }}
                    </Badge>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as OnlineStoreSettingRow).status === 'active' ? 'success' : 'warning'">
                        {{ (row as OnlineStoreSettingRow).status }}
                    </Badge>
                </template>
                <template #cell-link="{ row }">
                    <a
                        v-if="(row as OnlineStoreSettingRow).is_published"
                        :href="(row as OnlineStoreSettingRow).public_url"
                        target="_blank"
                        rel="noopener"
                        class="text-sm text-accent hover:underline"
                    >
                        Open
                    </a>
                    <span v-else class="text-ink-muted">—</span>
                </template>
                <template #actions="{ row }">
                    <div class="inline-flex items-center justify-end gap-0.5">
                        <Link
                            :href="route('admin.online-store-settings.edit', row.id)"
                            class="inline-flex items-center justify-center rounded-md p-1.5 text-ink-muted transition hover:bg-surface-muted hover:text-accent"
                            :aria-label="t('common.edit')"
                            :title="t('common.edit')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </Link>
                        <button
                            v-if="can('online_store.manage')"
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-1.5 text-ink-muted transition hover:bg-surface-muted hover:text-accent"
                            :aria-label="(row as OnlineStoreSettingRow).is_published ? 'Unpublish' : 'Publish'"
                            :title="(row as OnlineStoreSettingRow).is_published ? 'Unpublish' : 'Publish'"
                            @click="togglePublish(row.id)"
                        >
                            <svg
                                v-if="(row as OnlineStoreSettingRow).is_published"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m13.5 13.5L21 21m-4.272-4.272A10.45 10.45 0 0112 19.5" />
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
