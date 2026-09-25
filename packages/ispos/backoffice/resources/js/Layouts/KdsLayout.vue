<script setup lang="ts">
import Toast from '@/Components/ui/Toast.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

defineProps<{
    storeName?: string;
}>();

const page = usePage();
const now = ref(new Date());
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const clock = computed(() =>
    now.value.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
);

const staffName = computed(() => page.props.auth.user?.name ?? 'Kitchen');
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 text-zinc-100">
        <header class="flex shrink-0 items-center justify-between border-b border-zinc-800 bg-zinc-900/90 px-4 py-3 backdrop-blur">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 font-display text-sm font-bold text-zinc-950">
                    KDS
                </div>
                <div>
                    <p class="font-display text-sm font-semibold text-white">{{ storeName ?? 'Kitchen Display' }}</p>
                    <p class="text-xs text-zinc-400">Live order queue</p>
                </div>
            </div>

            <div class="flex items-center gap-6 text-sm">
                <div class="hidden text-right sm:block">
                    <p class="font-medium text-white">{{ staffName }}</p>
                    <p class="text-xs text-zinc-400">Kitchen staff</p>
                </div>
                <div class="font-mono text-lg tabular-nums text-amber-400">{{ clock }}</div>
                <Link
                    :href="route('dashboard')"
                    class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-300 transition hover:border-zinc-500 hover:text-white"
                >
                    Exit
                </Link>
            </div>
        </header>

        <main class="flex min-h-0 flex-1 flex-col">
            <slot />
        </main>

        <Toast />
    </div>
</template>
