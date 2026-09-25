<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import KdsLayout from '@/Layouts/KdsLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface TicketLine {
    id: string;
    name: string;
    qty: string;
    modifiers: Array<{ group_name: string; option_name: string }>;
    components: Array<{ name: string; quantity: string }>;
}

interface Ticket {
    id: string;
    ticket_number: string;
    sale_number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled';
    item_count: number;
    queued_at: string | null;
    register_name?: string | null;
    cashier_name?: string | null;
    lines: TicketLine[];
}

const props = defineProps<{
    store: { id: string; store_name: string; store_code: string };
    tickets: Ticket[];
}>();

const { t } = useLocale();

const tickets = ref<Ticket[]>([...props.tickets]);
const updatingId = ref<string | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const grouped = computed(() => ({
    pending: tickets.value.filter((ticket) => ticket.status === 'pending'),
    preparing: tickets.value.filter((ticket) => ticket.status === 'preparing'),
    ready: tickets.value.filter((ticket) => ticket.status === 'ready'),
}));

const columns = computed(() => [
    { key: 'pending', title: t('kds.columnNew'), tickets: grouped.value.pending, accent: 'border-amber-500/40 bg-amber-500/5' },
    { key: 'preparing', title: t('kds.columnPreparing'), tickets: grouped.value.preparing, accent: 'border-sky-500/40 bg-sky-500/5' },
    { key: 'ready', title: t('kds.columnReady'), tickets: grouped.value.ready, accent: 'border-emerald-500/40 bg-emerald-500/5' },
]);

function formatTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

function statusLabel(status: Ticket['status']): string {
    return {
        pending: t('kds.statusNew'),
        preparing: t('kds.statusPreparing'),
        ready: t('kds.statusReady'),
        completed: t('kds.statusDone'),
        cancelled: t('kds.statusCancelled'),
    }[status];
}

function nextStatus(status: Ticket['status']): Ticket['status'] | null {
    if (status === 'pending') {
        return 'preparing';
    }
    if (status === 'preparing') {
        return 'ready';
    }
    if (status === 'ready') {
        return 'completed';
    }

    return null;
}

function actionLabel(status: Ticket['status']): string {
    if (status === 'pending') {
        return t('kds.startPrep');
    }
    if (status === 'preparing') {
        return t('kds.markReady');
    }
    if (status === 'ready') {
        return t('kds.bump');
    }

    return '';
}

async function refreshTickets() {
    try {
        const response = await axios.get(route('kds.tickets.index'));
        tickets.value = response.data.tickets ?? [];
    } catch {
        // Keep last known tickets when polling fails offline.
    }
}

async function advanceTicket(ticket: Ticket) {
    const next = nextStatus(ticket.status);
    if (!next) {
        return;
    }

    updatingId.value = ticket.id;

    try {
        const response = await axios.patch(route('kds.tickets.update', ticket.id), { status: next });
        tickets.value = response.data.tickets ?? tickets.value.filter((row) => row.id !== ticket.id);
    } catch {
        router.reload({ only: ['tickets'] });
    } finally {
        updatingId.value = null;
    }
}

onMounted(() => {
    pollTimer = setInterval(refreshTickets, 5000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <Head :title="t('nav.kds_index')" />

    <KdsLayout :store-name="store.store_name">
        <div class="flex min-h-0 flex-1 flex-col p-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-display text-xl font-bold text-white">{{ t('kds.orderQueue') }}</h1>
                    <p class="text-sm text-zinc-400">{{ t('kds.activeTickets', { count: tickets.length }) }}</p>
                </div>
                <Button variant="secondary" size="sm" @click="refreshTickets">{{ t('kds.refresh') }}</Button>
            </div>

            <div v-if="tickets.length === 0" class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/40 p-12 text-center">
                <div>
                    <p class="font-display text-lg font-semibold text-white">{{ t('kds.noTickets') }}</p>
                    <p class="mt-2 text-sm text-zinc-400">{{ t('kds.noTicketsHint') }}</p>
                </div>
            </div>

            <div v-else class="grid min-h-0 flex-1 gap-4 lg:grid-cols-3">
                <section
                    v-for="column in columns"
                    :key="column.key"
                    class="flex min-h-0 flex-col rounded-2xl border border-zinc-800 bg-zinc-900/50"
                >
                    <div class="border-b border-zinc-800 px-4 py-3">
                        <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-zinc-300">{{ column.title }}</h2>
                        <p class="text-xs text-zinc-500">{{ t('kds.ticketCount', { count: column.tickets.length }) }}</p>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto p-3">
                        <article
                            v-for="ticket in column.tickets"
                            :key="ticket.id"
                            class="rounded-xl border p-4 shadow-sm"
                            :class="column.accent"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-lg font-bold text-white">{{ ticket.ticket_number }}</p>
                                    <p class="text-xs text-zinc-400">{{ ticket.sale_number }}</p>
                                </div>
                                <span class="rounded-full bg-zinc-950/40 px-2 py-1 text-xs font-medium text-zinc-200">
                                    {{ statusLabel(ticket.status) }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-zinc-400">
                                <span>{{ formatTime(ticket.queued_at) }}</span>
                                <span v-if="ticket.register_name">{{ ticket.register_name }}</span>
                                <span v-if="ticket.cashier_name">{{ ticket.cashier_name }}</span>
                            </div>

                            <ul class="mt-4 space-y-3">
                                <li v-for="line in ticket.lines" :key="line.id" class="rounded-lg bg-zinc-950/30 p-3">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="font-medium text-white">{{ line.name }}</p>
                                        <span class="font-mono text-sm text-amber-300">×{{ line.qty }}</span>
                                    </div>
                                    <ul v-if="line.modifiers.length" class="mt-2 space-y-1 text-xs text-zinc-300">
                                        <li v-for="(modifier, index) in line.modifiers" :key="`${line.id}-mod-${index}`">
                                            + {{ modifier.option_name }}
                                        </li>
                                    </ul>
                                    <ul v-if="line.components.length" class="mt-2 space-y-1 text-xs text-zinc-400">
                                        <li v-for="(component, index) in line.components" :key="`${line.id}-comp-${index}`">
                                            • {{ component.name }}
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <Button
                                v-if="nextStatus(ticket.status)"
                                class="mt-4 w-full"
                                :disabled="updatingId === ticket.id"
                                @click="advanceTicket(ticket)"
                            >
                                {{ actionLabel(ticket.status) }}
                            </Button>
                        </article>

                        <p v-if="column.tickets.length === 0" class="py-8 text-center text-sm text-zinc-500">{{ t('kds.noTicketsInColumn') }}</p>
                    </div>
                </section>
            </div>
        </div>
    </KdsLayout>
</template>
