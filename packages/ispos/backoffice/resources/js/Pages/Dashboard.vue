<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import Alert from '@/Components/ui/Alert.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatReportMoney } from '@/Composables/useReportFilters';
import { usePermissions } from '@/Composables/usePermissions';
import type { ProductRecord } from '@/types/product';
import type { SalesTrendPoint } from '@/types/salesReport';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        today_sales: number;
        transactions: number;
        average_transaction: number;
        gross_profit: number;
        low_stock_count: number;
    };
    salesTrend: SalesTrendPoint[];
    lowStockItems: ProductRecord[];
}>();

const pageModule = useModulePage('dashboard');
const { t, stat } = useLocale();
const page = usePage();
const { can } = usePermissions();
const posAppUrl = computed(() => (page.props.app as { pos_url?: string })?.pos_url ?? 'http://127.0.0.1:8003/pos');

const greeting = computed(() => t('dashboard.greeting'));
const userFirstName = computed(() => page.props.auth.user?.name?.split(' ')[0] ?? t('dashboard.guestName'));

const lowStockBanner = computed(() =>
    props.stats.low_stock_count === 1
        ? t('dashboard.lowStockBanner', { count: props.stats.low_stock_count })
        : t('dashboard.lowStockBannerPlural', { count: props.stats.low_stock_count }),
);

const lowStockHint = computed(() =>
    props.stats.low_stock_count ? t('dashboard.lowStockHint') : t('dashboard.lowStockHealthyHint'),
);

const maxTrendTotal = computed(() =>
    Math.max(...props.salesTrend.map((p) => parseFloat(p.total) || 0), 1),
);
</script>

<template>
    <Head :title="pageModule.header" />

    <AppLayout>
        <template #header>{{ pageModule.header }}</template>
        <template #subheader>{{ greeting }}, {{ userFirstName }}</template>

        <div class="space-y-5">
            <Alert
                v-if="can('inventory.view') && stats.low_stock_count > 0"
                variant="warning"
            >
                <span>
                    {{ lowStockBanner }}
                </span>
                <Link
                    :href="route('admin.inventory.low-stock')"
                    class="ml-2 font-medium underline underline-offset-2"
                >
                    {{ t('dashboard.viewLowStock') }}
                </Link>
            </Alert>

            <section class="ui-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <Badge variant="accent">{{ t('dashboard.live') }}</Badge>
                        <Badge variant="neutral">{{ t('dashboard.posAndSales') }}</Badge>
                    </div>
                    <h2 class="font-display text-xl font-bold tracking-tight text-ink sm:text-2xl">{{ t('dashboard.operationsOverview') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-relaxed text-ink-muted">
                        {{ t('dashboard.heroDescription') }}
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a
                        :href="route('admin.stores.index')"
                        class="inline-flex items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-medium text-ink shadow-sm transition hover:bg-surface-muted"
                    >
                        {{ t('dashboard.manageStores') }}
                    </a>
                    <a
                        v-if="can('pos.access')"
                        :href="posAppUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-accent-hover dark:text-zinc-950"
                    >
                        {{ t('dashboard.openPos') }}
                    </a>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    :label="stat('todaysSales')"
                    :value="formatReportMoney(stats.today_sales)"
                    :hint="t('dashboard.todaysSalesHint')"
                    :trend="stats.transactions > 0 ? 'up' : 'neutral'"
                />
                <StatCard
                    :label="stat('transactions')"
                    :value="stats.transactions"
                    :hint="t('dashboard.transactionsHint')"
                    :trend="stats.transactions > 0 ? 'up' : 'neutral'"
                />
                <StatCard
                    v-if="can('inventory.view')"
                    :label="stat('lowStockItems')"
                    :value="stats.low_stock_count"
                    :hint="lowStockHint"
                    :trend="stats.low_stock_count ? 'down' : 'up'"
                />
                <StatCard
                    v-else
                    :label="stat('activeStores')"
                    value="—"
                    :hint="t('dashboard.fromOrganizationModule')"
                    trend="up"
                />
                <StatCard
                    :label="stat('openRegisters')"
                    value="—"
                    :hint="t('dashboard.fromOrganizationModule')"
                    trend="neutral"
                />
            </section>

            <section class="grid gap-5 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <Card :title="t('dashboard.salesTrend')" :description="t('dashboard.salesLast7Days')">
                        <div v-if="!salesTrend.length" class="flex h-60 items-center justify-center text-sm text-ink-muted">
                            {{ t('dashboard.noSalesData') }}
                        </div>
                        <div v-else class="flex h-60 items-end gap-1.5 rounded-xl bg-surface-muted/40 p-5">
                            <div
                                v-for="point in salesTrend"
                                :key="point.date"
                                class="flex-1 rounded-md bg-gradient-to-t from-accent/70 to-accent/20 transition-all duration-300 hover:from-accent hover:to-accent/40"
                                :style="{ height: `${Math.max(8, (parseFloat(point.total) / maxTrendTotal) * 100)}%` }"
                                :title="`${point.date}: ${formatReportMoney(point.total)}`"
                            />
                        </div>
                        <div v-if="can('reports.view')" class="mt-3 text-right">
                            <Link :href="route('admin.reports.sales-summary.index')" class="text-sm font-medium text-accent hover:underline">
                                {{ t('dashboard.viewSalesSummary') }}
                            </Link>
                        </div>
                    </Card>
                </div>

                <div class="lg:col-span-2">
                    <Card
                        v-if="can('inventory.view')"
                        :title="t('dashboard.lowStockAlerts')"
                        :description="t('dashboard.lowStockAlertsDesc')"
                    >
                        <div v-if="!lowStockItems.length" class="py-6 text-center text-sm text-ink-muted">
                            {{ t('dashboard.noLowStock') }}
                        </div>
                        <div v-else class="space-y-2.5">
                            <Link
                                v-for="item in lowStockItems"
                                :key="item.id"
                                :href="route('admin.products.edit', item.id)"
                                class="flex items-center justify-between rounded-xl border border-line-subtle bg-surface-muted/30 px-4 py-3 transition hover:bg-surface-muted/60"
                            >
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ item.name }}</p>
                                    <p class="text-xs text-ink-muted">{{ item.sku }}</p>
                                </div>
                                <div class="text-right">
                                    <Badge :variant="item.stock_status === 'out' ? 'danger' : 'warning'">
                                        {{ item.qty }} / {{ item.warning_qty ?? '—' }}
                                    </Badge>
                                </div>
                            </Link>
                        </div>
                        <div v-if="stats.low_stock_count > lowStockItems.length" class="mt-4">
                            <Link
                                :href="route('admin.inventory.low-stock')"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                {{ t('dashboard.viewAllItems', { count: stats.low_stock_count }) }}
                            </Link>
                        </div>
                    </Card>

                    <Card v-else :title="t('dashboard.storePerformance')" :description="t('dashboard.noStoreData')">
                        <div class="space-y-2.5">
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="flex items-center justify-between rounded-xl border border-line-subtle bg-surface-muted/30 px-4 py-3 transition hover:bg-surface-muted/60"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-accent-soft text-xs font-bold text-accent">{{ n }}</span>
                                    <div class="h-2.5 w-24 rounded-full bg-line/60" />
                                </div>
                                <div class="h-2.5 w-14 rounded-full bg-accent/25" />
                            </div>
                        </div>
                    </Card>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
