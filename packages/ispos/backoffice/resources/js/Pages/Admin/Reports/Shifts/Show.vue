<script setup lang="ts">
import { computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IndexPageHeader from '@/Components/ui/IndexPageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatReportMoney } from '@/Composables/useReportFilters';
import type { ShiftReportDetail } from '@/types/shiftReport';
import { Head } from '@inertiajs/vue3';

const page = useModulePage('shiftReports');
const { t, stat, column, card, field } = useLocale();

const props = defineProps<{
    report: ShiftReportDetail;
}>();

const { shift, summary, payments, sales } = props.report;

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

const saleColumns = computed(() => [
    { key: 'completed_at', label: column('date') },
    { key: 'sale_number', label: column('saleNumber') },
    { key: 'customer_name', label: column('customer') },
    { key: 'status', label: t('common.status') },
    { key: 'grand_total', label: column('total') },
]);
</script>

<template>
    <Head :title="page.showHeader" />
    <AppLayout>
        <template #header>{{ page.showHeader }}</template>
        <template #subheader>{{ page.subheader }}</template>

        <div class="index-page flex flex-col gap-2">
            <IndexPageHeader
                :back-href="route('admin.reports.shift-reports.index')"
                :eyebrow="page.eyebrow"
                :title="page.title"
                :description="page.description"
            >
                <template #meta>
                    <Badge :variant="shift.status === 'open' ? 'warning' : 'success'">{{ shift.status }}</Badge>
                </template>
            </IndexPageHeader>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard :label="stat('completedSales')" :value="String(summary.transaction_count)" />
                <StatCard :label="stat('grossSales')" :value="formatReportMoney(summary.gross_sales)" />
                <StatCard :label="stat('voids')" :value="`${summary.void_count} · ${formatReportMoney(summary.void_total)}`" />
                <StatCard :label="stat('netSales')" :value="formatReportMoney(summary.net_sales)" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card :title="card('cashDrawer')">
                    <dl class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ stat('openingFloat') }}</dt>
                            <dd class="font-mono tabular-nums">{{ formatReportMoney(shift.opening_float) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ stat('expectedCash') }}</dt>
                            <dd class="font-mono tabular-nums">{{ formatReportMoney(shift.expected_cash) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ stat('countedCash') }}</dt>
                            <dd class="font-mono tabular-nums">
                                {{ shift.closing_float !== null ? formatReportMoney(shift.closing_float) : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-line pt-3 font-medium">
                            <dt>{{ stat('variance') }}</dt>
                            <dd
                                class="font-mono tabular-nums"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400': Number(shift.variance) === 0,
                                    'text-amber-600 dark:text-amber-400': shift.variance !== null && Number(shift.variance) !== 0,
                                }"
                            >
                                {{ shift.variance !== null ? formatReportMoney(shift.variance) : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 text-xs text-ink-muted">
                            <dt>{{ field('closedAt') }}</dt>
                            <dd>{{ formatDateTime(shift.closed_at) }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card :title="card('paymentsByMethod')">
                    <EmptyState
                        v-if="!payments.length"
                        :title="page.emptyTitle"
                        :description="page.emptyDescription"
                    />
                    <ul v-else class="space-y-3 text-sm">
                        <li v-for="payment in payments" :key="payment.payment_method" class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium capitalize">{{ payment.payment_method }}</p>
                                <p class="text-xs text-ink-muted">{{ t('fields.transactionsCount', { count: payment.count }) }}</p>
                            </div>
                            <span class="font-mono tabular-nums">{{ formatReportMoney(payment.amount) }}</span>
                        </li>
                    </ul>
                </Card>
            </div>

            <Card :title="card('shiftSales')">
                <EmptyState v-if="!sales.length" :title="page.emptyTitle" :description="page.emptyDescription" />
                <DataTable v-else :columns="saleColumns" :rows="sales" viewport-fit compact>
                    <template #cell-completed_at="{ row }">
                        {{ formatDateTime(row.completed_at) }}
                    </template>
                    <template #cell-customer_name="{ row }">
                        {{ row.customer_name || '—' }}
                    </template>
                    <template #cell-status="{ row }">
                        <Badge :variant="row.status === 'completed' ? 'success' : 'neutral'">{{ row.status }}</Badge>
                    </template>
                    <template #cell-grand_total="{ row }">
                        <span class="font-mono tabular-nums">{{ formatReportMoney(row.grand_total) }}</span>
                    </template>
                </DataTable>
            </Card>
        </div>
    </AppLayout>
</template>
