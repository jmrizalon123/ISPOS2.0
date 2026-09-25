<script setup lang="ts">
import Dropdown from '@/Components/ui/Dropdown.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import NavIcon from '@/Components/ui/NavIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const { t } = useI18n();

const user = computed(() => page.props.auth.user);
const twoFactorEnabled = computed(() => Boolean(user.value?.two_factor_enabled));

const menuItems = computed(() => [
    {
        href: route('profile.edit'),
        icon: 'profile' as const,
        label: t('userMenu.profile'),
    },
    {
        href: `${route('profile.edit')}#password`,
        icon: 'settings' as const,
        label: t('userMenu.changePassword'),
    },
    {
        href: `${route('profile.edit')}#two-factor`,
        icon: 'roles' as const,
        label: t('userMenu.twoFactor'),
        badge: twoFactorEnabled.value ? t('userMenu.enabled') : t('userMenu.disabled'),
        badgeVariant: twoFactorEnabled.value ? ('success' as const) : ('neutral' as const),
    },
]);
</script>

<template>
    <Dropdown align="right" width="72" content-classes="overflow-hidden bg-surface p-0">
        <template #trigger="{ open }">
            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-accent-soft text-xs font-bold text-accent ring-1 ring-accent/20 transition hover:ring-2 hover:ring-accent/30"
                :class="open ? 'ring-2 ring-accent/35' : ''"
                :title="user?.name ?? t('common.accountMenu')"
            >
                <img
                    v-if="user?.avatar_url"
                    :src="user.avatar_url"
                    :alt="user?.name ?? ''"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ user?.name?.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase() }}</span>
            </button>
        </template>

        <template #content>
            <div class="border-b border-line/80 bg-surface-muted/35 px-4 py-4">
                <div class="flex items-center gap-3">
                    <Avatar :src="user?.avatar_url" :name="user?.name" size="md" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink">{{ user?.name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ user?.email }}</p>
                    </div>
                </div>
            </div>

            <nav class="p-2" aria-label="Account menu">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink transition hover:bg-surface-muted"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-surface-muted/80 text-ink-muted ring-1 ring-line/60 transition group-hover:bg-accent-soft group-hover:text-accent group-hover:ring-accent/20"
                    >
                        <NavIcon :name="item.icon" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="min-w-0 flex-1 leading-snug">{{ item.label }}</span>
                    <Badge v-if="item.badge" class="shrink-0" :variant="item.badgeVariant">
                        {{ item.badge }}
                    </Badge>
                    <NavIcon
                        v-else
                        name="chevron-left"
                        class="h-4 w-4 shrink-0 rotate-180 text-ink-muted/50 transition group-hover:text-ink-muted"
                    />
                </Link>
            </nav>
        </template>
    </Dropdown>
</template>
