<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useModulePage } from '@/Composables/useModulePage';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import TableAuditStamp from '@/Components/ui/TableAuditStamp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { usePermissions } from '@/Composables/usePermissions';
import { paginatedRowNumber, useAuditTableColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import { useRecordCountLabel } from '@/Composables/useIndexPageHeader';
import type { AuditableUser, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = useModulePage('users');
const { t } = useI18n();
const { emptyAction } = useLocale();
const { can } = usePermissions();

const auditCols = useAuditTableColumns();
const lineCol = useLineTableColumn();

const props = defineProps<{
    users: Paginated<{
        id: string;
        name: string;
        username: string | null;
        avatar_url: string | null;
        email: string;
        status: string;
        created_at: string;
        updated_at: string;
        company?: { id: string; name: string } | null;
        roles?: Array<{ id: number; name: string }>;
        creator?: AuditableUser | null;
        updater?: AuditableUser | null;
    }>;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const filterQuery = computed(() => ({ search: search.value || undefined }));
const hasActiveFilters = computed(() => !!search.value);
const countLabel = useRecordCountLabel(() => props.users.total, 'user');

watch(search, (value) => {
    router.get(route('admin.users.index'), { search: value || undefined }, { preserveState: true, replace: true });
});

function clearFilters() {
    search.value = '';
}

const columns = computed(() => [
    lineCol.value,
    { key: 'name', label: t('common.name') },
    { key: 'username', label: t('columns.username') },
    { key: 'email', label: t('columns.email') },
    { key: 'company', label: t('common.company') },
    { key: 'roles', label: t('columns.roles') },
    { key: 'status', label: t('common.status') },
    ...auditCols.value,
]);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="users.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template #actions>
                    <Link v-if="can('roles.view')" :href="route('admin.users.role-members.index')">
                        <Button variant="secondary">{{ t('pages.roleMembers.title') }}</Button>
                    </Link>
                    <Link :href="route('admin.users.store-employees.index')">
                        <Button variant="secondary">{{ t('pages.storeEmployees.title') }}</Button>
                    </Link>
                    <Link :href="route('admin.users.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
            </IndexToolbar>

            <EmptyState
                v-if="!users.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="emptyAction('createUser')"
                @action="router.visit(route('admin.users.create'))"
            />

            <DataTable v-else :columns="columns" :rows="users.data" :paginated="users" pagination-route="admin.users.index" :pagination-query="filterQuery" sticky-actions viewport-fit compact>
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(users.from, index) }}</span>
                </template>
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-2.5">
                        <Avatar :src="row.avatar_url" :name="row.name" size="xs" />
                        <Link :href="route('admin.users.edit', row.id)" class="font-medium text-accent hover:underline">
                            {{ row.name }}
                        </Link>
                    </div>
                </template>
                <template #cell-username="{ row }">
                    <span v-if="row.username" class="font-mono text-xs text-ink">{{ row.username }}</span>
                    <span v-else class="text-ink-muted">—</span>
                </template>
                <template #cell-company="{ row }">
                    {{ row.company?.name ?? '—' }}
                </template>
                <template #cell-roles="{ row }">
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="role in row.roles ?? []" :key="role.name" variant="accent">
                            {{ role.name }}
                        </Badge>
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</Badge>
                </template>
                <template #cell-creator="{ row }">
                    <TableAuditStamp :user="row.creator" :at="row.created_at" />
                </template>
                <template #cell-last_modifier="{ row }">
                    <TableAuditStamp :user="row.updater" :at="row.updated_at" />
                </template>
                <template #actions="{ row }">
                    <Link :href="route('admin.users.edit', row.id)" class="text-sm text-accent hover:underline">{{ t('common.edit') }}</Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
