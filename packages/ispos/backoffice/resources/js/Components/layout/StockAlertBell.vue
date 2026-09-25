<script setup lang="ts">
import Dropdown from '@/Components/ui/Dropdown.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import { useLocale } from '@/Composables/useLocale';
import { usePermissions } from '@/Composables/usePermissions';
import type { StockStatus } from '@/types/product';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LowStockPreviewItem {
    id: string;
    name: string;
    sku: string;
    qty: number | string;
    warning_qty: number | string | null;
    stock_status: StockStatus;
}

const page = usePage();
const { can } = usePermissions();
const { t } = useLocale();

const visible = computed(() => can('inventory.view'));

const alertCount = computed(() => page.props.alerts?.low_stock_count ?? 0);

const previewItems = computed(() => (page.props.alerts?.low_stock_items ?? []) as LowStockPreviewItem[]);

const hasAlerts = computed(() => alertCount.value > 0);

const alertTitle = computed(() => {
    if (!hasAlerts.value) {
        return t('messages.noStockAlerts');
    }

    return alertCount.value === 1
        ? t('messages.lowStockAlert', { count: alertCount.value })
        : t('messages.lowStockAlertsCount', { count: alertCount.value });
});

const alertSummary = computed(() => {
    if (!hasAlerts.value) {
        return t('pages.lowStock.emptyDescription');
    }

    return alertCount.value === 1
        ? t('dashboard.lowStockBanner', { count: alertCount.value })
        : t('dashboard.lowStockBannerPlural', { count: alertCount.value });
});

const alertLinkLabel = computed(() =>
    hasAlerts.value ? t('forms.actions.viewAllAlerts') : t('messages.openLowStockPage'),
);

function statusLabel(status: StockStatus): string {
    if (status === 'out') {
        return t('filters.outOfStock');
    }

    if (status === 'low') {
        return t('filters.lowStock');
    }

    return status;
}

function formatQty(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}
</script>

<template>
    <Dropdown
        v-if="visible"
        align="right"
        width="80"
        content-classes="notif-panel overflow-hidden bg-surface p-0"
        :close-on-content-click="false"
    >
        <template #trigger="{ open }">
            <button
                type="button"
                class="topbar-icon-btn topbar-icon-btn--bell"
                :class="{ 'topbar-icon-btn--open': open }"
                :title="alertTitle"
                :aria-label="alertTitle"
                :aria-expanded="open"
            >
                <NavIcon name="bell" subtle />
                <span
                    v-if="hasAlerts"
                    class="topbar-icon-btn__badge"
                >
                    {{ alertCount > 99 ? '99+' : alertCount }}
                </span>
            </button>
        </template>

        <template #content="{ close }">
            <div class="notif-panel__header">
                <div class="notif-panel__header-main">
                    <div class="notif-panel__header-icon" aria-hidden="true">
                        <NavIcon name="bell" subtle />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold tracking-tight text-ink">
                                {{ t('pages.lowStock.title') }}
                            </p>
                            <span
                                v-if="hasAlerts"
                                class="notif-panel__count"
                            >
                                {{ alertCount > 99 ? '99+' : alertCount }}
                            </span>
                        </div>
                        <p class="mt-0.5 truncate text-[12px] leading-snug text-ink-muted">
                            {{ alertSummary }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="previewItems.length" class="notif-panel__list">
                <Link
                    v-for="item in previewItems"
                    :key="item.id"
                    :href="route('admin.products.edit', item.id)"
                    class="notif-panel__item"
                    @click="close"
                >
                    <span
                        class="notif-panel__status"
                        :class="item.stock_status === 'out' ? 'notif-panel__status--out' : 'notif-panel__status--low'"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-medium text-ink">{{ item.name }}</p>
                        <p class="mt-0.5 truncate text-[11px] text-ink-muted">
                            {{ item.sku }}
                            <span class="text-ink-muted/50">·</span>
                            {{ statusLabel(item.stock_status) }}
                        </p>
                    </div>
                    <div class="notif-panel__qty shrink-0 text-right">
                        <p class="text-[13px] font-semibold tabular-nums text-ink">
                            {{ formatQty(item.qty) }}
                        </p>
                        <p class="text-[10px] tabular-nums text-ink-muted">
                            / {{ formatQty(item.warning_qty) }}
                        </p>
                    </div>
                </Link>
            </div>

            <div v-else class="notif-panel__empty">
                <div class="notif-panel__empty-icon" aria-hidden="true">
                    <NavIcon name="bell" subtle />
                </div>
                <p class="text-sm font-medium text-ink">{{ t('messages.noStockAlerts') }}</p>
                <p class="mt-1 max-w-[14rem] text-center text-[12px] leading-relaxed text-ink-muted">
                    {{ t('pages.lowStock.emptyDescription') }}
                </p>
            </div>

            <div class="notif-panel__footer">
                <Link
                    :href="route('admin.inventory.low-stock')"
                    class="notif-panel__cta"
                    @click="close"
                >
                    {{ alertLinkLabel }}
                    <span aria-hidden="true">→</span>
                </Link>
            </div>
        </template>
    </Dropdown>
</template>
