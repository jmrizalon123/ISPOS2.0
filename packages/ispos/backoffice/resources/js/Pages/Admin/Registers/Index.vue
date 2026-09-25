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
import { useBackofficeScope } from '@/Composables/useBackofficeScope';
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
import { paginatedRowNumber, useColumns, useLineTableColumn } from '@/Composables/useTableColumns';
import type { AuditableUser, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { can } = usePermissions();
const { isStoreScope } = useBackofficeScope();
const page = useModulePage('registers');
const { t } = useI18n();
const { emptyAction } = useLocale();
const lineCol = useLineTableColumn();

const registerColumnKeys = [
    'register_code',
    'register_name',
    'min',
    'permit_number',
    'device_serial',
    'reset_registration',
    'company_id',
    'store_id',
    'terminal_code',
    'terminal_name',
    'device_id',
    'device_name',
    'device_type',
    'ip_address',
    'mac_address',
    'printer_id',
    'cash_drawer_id',
    'customer_display_id',
    'kds_station_id',
    'receipt_printer_name',
    'receipt_printer_type',
    'receipt_printer_ip',
    'receipt_printer_port',
    'drawer_open_method',
    'allow_cash_sales',
    'allow_card_sales',
    'allow_gcash_sales',
    'allow_maya_sales',
    'allow_other_payments',
    'allow_discount',
    'allow_void',
    'allow_refund',
    'allow_reprint',
    'allow_price_override',
    'allow_open_drawer',
    'require_cashier_login',
    'require_manager_approval',
    'auto_print_receipt',
    'auto_print_kitchen_order',
    'auto_print_customer_receipt',
    'enable_customer_display',
    'enable_kds',
    'enable_ncs',
    'online_order_enabled',
    'offline_mode_enabled',
    'sync_enabled',
    'last_sync_at',
    'last_z_read_at',
    'current_shift_id',
    'current_cashier_id',
    'is_active',
    'opened_at',
    'closed_at',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at',
    'deleted_at',
] as const;

const booleanColumnKeys = [
    'allow_cash_sales',
    'allow_card_sales',
    'allow_gcash_sales',
    'allow_maya_sales',
    'allow_other_payments',
    'allow_discount',
    'allow_void',
    'allow_refund',
    'allow_reprint',
    'allow_price_override',
    'allow_open_drawer',
    'require_cashier_login',
    'require_manager_approval',
    'auto_print_receipt',
    'auto_print_kitchen_order',
    'auto_print_customer_receipt',
    'enable_customer_display',
    'enable_kds',
    'enable_ncs',
    'online_order_enabled',
    'offline_mode_enabled',
    'sync_enabled',
    'is_active',
    'reset_registration',
] as const;

const dateColumnKeys = [
    'last_sync_at',
    'last_z_read_at',
    'opened_at',
    'closed_at',
    'created_at',
    'updated_at',
    'deleted_at',
] as const;

type RegisterRow = {
    id: string;
    uuid: string | null;
    company_id: string | null;
    store_id: string;
    register_code: string;
    register_name: string;
    terminal_code: string | null;
    terminal_name: string | null;
    device_id: string | null;
    device_name: string | null;
    device_type: string | null;
    ip_address: string | null;
    mac_address: string | null;
    printer_id: string | null;
    cash_drawer_id: string | null;
    customer_display_id: string | null;
    kds_station_id: string | null;
    receipt_printer_name: string | null;
    receipt_printer_type: string | null;
    receipt_printer_ip: string | null;
    receipt_printer_port: number | null;
    drawer_open_method: string | null;
    allow_cash_sales: boolean;
    allow_card_sales: boolean;
    allow_gcash_sales: boolean;
    allow_maya_sales: boolean;
    allow_other_payments: boolean;
    allow_discount: boolean;
    allow_void: boolean;
    allow_refund: boolean;
    allow_reprint: boolean;
    allow_price_override: boolean;
    allow_open_drawer: boolean;
    require_cashier_login: boolean;
    require_manager_approval: boolean;
    auto_print_receipt: boolean;
    auto_print_kitchen_order: boolean;
    auto_print_customer_receipt: boolean;
    enable_customer_display: boolean;
    enable_kds: boolean;
    enable_ncs: boolean;
    online_order_enabled: boolean;
    offline_mode_enabled: boolean;
    sync_enabled: boolean;
    last_sync_at: string | null;
    last_z_read_at: string | null;
    current_shift_id: string | null;
    current_cashier_id: string | null;
    status: string;
    is_active: boolean;
    reset_registration: boolean;
    opened_at: string | null;
    closed_at: string | null;
    created_by: string | null;
    updated_by: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    company?: { id: string; name: string; company_code: string; display_name?: string | null } | null;
    store?: { id: string; store_name: string; store_code: string };
    device?: { id: string; name: string } | null;
    current_shift?: { id: string; status: string; opened_at: string | null } | null;
    current_cashier?: AuditableUser | null;
    creator?: AuditableUser | null;
    updater?: AuditableUser | null;
};

const props = defineProps<{
    registers: Paginated<RegisterRow>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { search: string; store_id: string; status: string };
}>();

const { search, storeId, status, hasActiveFilters, clearFilters, filterQuery } = useCatalogIndexFilters(
    'admin.registers.index',
    props.filters,
    ['search', 'store_id', 'status'],
    [],
    props.stores.map((store) => ({ ...store, company_id: '' })),
);

const registerCols = useColumns(registerColumnKeys.map((key) => ({ key })));

const showStoreFilter = computed(() => props.stores.length > 0);
const countLabel = useRecordCountLabel(() => props.registers.total, 'register');
const canCreateRegister = computed(() => !isStoreScope.value && can('registers.create'));
const canEditRegister = computed(() => !isStoreScope.value && can('registers.update'));

const statusCol = computed(() => ({
    key: 'status',
    label: t('columns.status'),
    sticky: 'end' as const,
    minWidth: 108,
    class: 'whitespace-nowrap',
}));

const columns = computed(() => [lineCol.value, ...registerCols.value, statusCol.value]);

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function formatBoolean(value: boolean | null | undefined) {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? t('common.yes') : t('common.no');
}

function formatNullable(value: string | number | null | undefined) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

function companyLabel(row: RegisterRow): string {
    if (!row.company) {
        return formatNullable(row.company_id);
    }

    return row.company.display_name || row.company.name;
}
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2" :class="registers.data.length ? 'index-page--fill' : undefined">
            <IndexPageHeader
                class="shrink-0"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge variant="neutral">{{ countLabel }}</Badge>
                </template>
                <template v-if="canCreateRegister" #actions>
                    <Link :href="route('admin.registers.create')">
                        <Button>{{ page.newButton }}</Button>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar class="shrink-0" :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="search" :placeholder="page.searchPlaceholder" class="!w-56" />
                <Select v-if="showStoreFilter" v-model="storeId" class="!w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.store_name }}</option>
                </Select>
                <StatusFilterSelect v-model="status" />
            </IndexToolbar>

            <EmptyState
                v-if="!registers.data.length"
                :title="page.emptyTitle"
                :description="page.emptyDescription"
                :action-label="canCreateRegister ? emptyAction('createRegister') : undefined"
                @action="canCreateRegister && router.visit(route('admin.registers.create'))"
            />

            <DataTable
                v-else
                :columns="columns"
                :rows="registers.data"
                :paginated="registers"
                pagination-route="admin.registers.index"
                :pagination-query="filterQuery"
                :sticky-actions="canEditRegister"
                viewport-fit
                compact
            >
                <template #cell-line="{ index }">
                    <span class="tabular-nums text-ink-muted">{{ paginatedRowNumber(registers.from, index) }}</span>
                </template>
                <template #cell-company_id="{ row }">
                    {{ companyLabel(row as RegisterRow) }}
                </template>
                <template #cell-store_id="{ row }">
                    {{ (row as RegisterRow).store?.store_name ?? formatNullable((row as RegisterRow).store_id) }}
                </template>
                <template #cell-register_code="{ row }">
                    <Link
                        v-if="canEditRegister"
                        :href="route('admin.registers.edit', (row as RegisterRow).id)"
                        class="font-medium text-accent hover:underline"
                    >
                        {{ (row as RegisterRow).register_code }}
                    </Link>
                    <span v-else class="font-medium text-ink">{{ (row as RegisterRow).register_code }}</span>
                </template>
                <template #cell-register_name="{ row }">
                    {{ (row as RegisterRow).register_name }}
                </template>
                <template #cell-device_id="{ row }">
                    {{ (row as RegisterRow).device?.name ?? formatNullable((row as RegisterRow).device_id) }}
                </template>
                <template #cell-current_shift_id="{ row }">
                    {{ formatNullable((row as RegisterRow).current_shift_id) }}
                </template>
                <template #cell-current_cashier_id="{ row }">
                    {{ (row as RegisterRow).current_cashier?.name ?? formatNullable((row as RegisterRow).current_cashier_id) }}
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="(row as RegisterRow).status === 'active' ? 'success' : 'neutral'">
                        {{ (row as RegisterRow).status }}
                    </Badge>
                </template>
                <template #cell-created_by="{ row }">
                    {{ (row as RegisterRow).creator?.name ?? formatNullable((row as RegisterRow).created_by) }}
                </template>
                <template #cell-updated_by="{ row }">
                    {{ (row as RegisterRow).updater?.name ?? formatNullable((row as RegisterRow).updated_by) }}
                </template>
                <template v-for="key in booleanColumnKeys" #[`cell-${key}`]="{ row }" :key="key">
                    <span class="text-sm">{{ formatBoolean((row as RegisterRow)[key]) }}</span>
                </template>
                <template v-for="key in dateColumnKeys" #[`cell-${key}`]="{ row }" :key="`date-${key}`">
                    <span class="text-sm text-ink-muted">{{ formatDate((row as RegisterRow)[key]) }}</span>
                </template>
                <template v-if="canEditRegister" #actions="{ row }">
                    <Link
                        :href="route('admin.registers.edit', (row as RegisterRow).id)"
                        class="text-sm text-accent hover:underline"
                    >
                        {{ t('common.edit') }}
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
