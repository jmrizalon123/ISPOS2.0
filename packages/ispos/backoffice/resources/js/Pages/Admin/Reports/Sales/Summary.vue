<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatReportMoney, useReportFilters } from '@/Composables/useReportFilters';
import type { SalesSummary, SalesTrendPoint, StoreSalesBreakdown } from '@/types/salesReport';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('salesSummary');
const { t, stat } = useLocale();

const props = defineProps<{
    summary: SalesSummary;
    trend: SalesTrendPoint[];
    storeBreakdown: StoreSalesBreakdown[];
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string }>;
    filters: { company_id: string; store_id: string; date_from: string; date_to: string; status: string };
}>();

const { companyId, storeId, dateFrom, dateTo, showCompanyFilter, hasActiveFilters, clearFilters } = useReportFilters(
    'admin.reports.sales-summary.index',
    props.filters,
    props.companies,
);

const maxTrendTotal = computed(() =>
    Math.max(...props.trend.map((p) => parseFloat(p.total) || 0), 1),
);
</script>

<template>
    <Head :title="page.header" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2">
            <IndexPageHeader
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #actions>
                    <Link :href="route('admin.reports.sales-register.index', filters)">
                        <Badge variant="neutral">Sales register →</Badge>
                    </Link>
                </template>
            </IndexPageHeader>

            <IndexToolbar :show-clear="hasActiveFilters" @clear="clearFilters">
                <Input v-model="dateFrom" type="date" class="!w-40" />
                <Input v-model="dateTo" type="date" class="!w-40" />
                <Select v-if="showCompanyFilter" v-model="companyId" class="!w-44">
                    <option value="">{{ t('common.allCompanies') }}</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.display_name || company.name }}
                    </option>
                </Select>
                <Select v-model="storeId" class="!w-44">
                    <option value="">{{ t('common.allStores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.store_name }}
                    </option>
                </Select>
            </IndexToolbar>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard :label="stat('grossSales')" :value="formatReportMoney(summary.gross_sales)" hint="Completed sales" trend="up" />
                <StatCard :label="stat('transactions')" :value="summary.transactions" hint="Completed checkouts" trend="neutral" />
                <StatCard :label="stat('averageTicket')" :value="formatReportMoney(summary.average_ticket)" hint="Per transaction" trend="neutral" />
                <StatCard :label="stat('voidedSales')" :value="summary.voided_count" hint="In selected period" :trend="summary.voided_count ? 'down' : 'up'" />
            </section>

            <section class="grid gap-5 lg:grid-cols-5">
                <Card class="lg:col-span-3" :title="page.title" :description="page.description">
                    <div class="flex h-60 items-end gap-1.5 rounded-xl bg-surface-muted/40 p-5">
                        <div
                            v-for="point in trend"
                            :key="point.date"
                            class="group relative flex-1"
                        >
                            <div
                                class="rounded-md bg-gradient-to-t from-accent/70 to-accent/20 transition-all hover:from-accent hover:to-accent/40"
                                :style="{ height: `${Math.max(8, (parseFloat(point.total) / maxTrendTotal) * 100)}%` }"
                            />
                            <span class="pointer-events-none absolute -bottom-6 left-1/2 hidden -translate-x-1/2 text-[10px] text-ink-muted group-hover:block">
                                {{ point.date.slice(5) }}
                            </span>
                        </div>
                    </div>
                </Card>

                <Card class="lg:col-span-2" :title="page.title" :description="page.description">
                    <div v-if="!storeBreakdown.length" class="py-6 text-center text-sm text-ink-muted">No sales in this period.</div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="(row, index) in storeBreakdown"
                            :key="row.store_id"
                            class="flex items-center justify-between rounded-lg border border-line-subtle px-3 py-2"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-accent">{{ index + 1 }}</span>
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ row.store_name }}</p>
                                    <p class="text-xs text-ink-muted">{{ row.count }} sale(s)</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{ formatReportMoney(row.total) }}</span>
                        </div>
                    </div>
                </Card>
            </section>

            <Card :title="page.title" :description="page.description">
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ stat('subtotal') }}</dt>
                        <dd class="text-lg font-semibold">{{ formatReportMoney(summary.gross_sales) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ stat('discounts') }}</dt>
                        <dd class="text-lg font-semibold text-emerald-600">{{ formatReportMoney(summary.discount_total) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ stat('taxCollected') }}</dt>
                        <dd class="text-lg font-semibold">{{ formatReportMoney(summary.tax_total) }}</dd>
                    </div>
                </dl>
            </Card>
        </div>
    </AppLayout>
</template>
