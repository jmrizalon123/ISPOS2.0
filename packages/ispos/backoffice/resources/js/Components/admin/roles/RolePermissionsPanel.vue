<script setup lang="ts">
import Input from '@/Components/ui/Input.vue';
import IndexToolbar from '@/Components/ui/IndexToolbar.vue';
import Badge from '@/Components/ui/Badge.vue';
import {
    PERMISSION_MODULES,
    permissionsForModule,
    selectedCountForModule,
    type PermissionModuleKey,
} from '@/config/permissionModules';
import { useI18n } from 'vue-i18n';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        permissions: string[];
        modelValue: string[];
        disabled?: boolean;
        showSearch?: boolean;
    }>(),
    {
        disabled: false,
        showSearch: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [string[]];
}>();

const { t } = useI18n();

const activeModule = ref<PermissionModuleKey>('general');
const permissionSearch = ref('');
const selectAllRef = ref<HTMLInputElement | null>(null);

const modulePermissions = computed(() => permissionsForModule(props.permissions, activeModule.value));

const filteredPermissions = computed(() => {
    const term = permissionSearch.value.trim().toLowerCase();

    if (!term) {
        return modulePermissions.value;
    }

    return modulePermissions.value.filter((permission) => permission.toLowerCase().includes(term));
});

const allSelectedInModule = computed(
    () =>
        modulePermissions.value.length > 0 &&
        modulePermissions.value.every((permission) => props.modelValue.includes(permission)),
);

const someSelectedInModule = computed(
    () =>
        modulePermissions.value.some((permission) => props.modelValue.includes(permission)) &&
        !allSelectedInModule.value,
);

watch([allSelectedInModule, someSelectedInModule], () => {
    if (selectAllRef.value) {
        selectAllRef.value.indeterminate = someSelectedInModule.value;
    }
}, { immediate: true });

watch(activeModule, () => {
    permissionSearch.value = '';
});

function togglePermission(permission: string) {
    if (props.disabled) {
        return;
    }

    if (props.modelValue.includes(permission)) {
        emit(
            'update:modelValue',
            props.modelValue.filter((item) => item !== permission),
        );

        return;
    }

    emit('update:modelValue', [...props.modelValue, permission]);
}

function toggleAllInModule() {
    if (props.disabled) {
        return;
    }

    if (allSelectedInModule.value) {
        emit(
            'update:modelValue',
            props.modelValue.filter((permission) => !modulePermissions.value.includes(permission)),
        );

        return;
    }

    emit('update:modelValue', [...new Set([...props.modelValue, ...modulePermissions.value])]);
}

function moduleSelectedCount(key: PermissionModuleKey): number {
    return selectedCountForModule(props.permissions, props.modelValue, key);
}
</script>

<template>
    <div class="role-permissions-panel">
        <div class="role-permissions-panel__tabs" role="tablist" :aria-label="t('rolesPanel.modules.label')">
            <button
                v-for="module in PERMISSION_MODULES"
                :key="module.key"
                type="button"
                role="tab"
                class="role-permissions-panel__tab"
                :class="{ 'is-active': activeModule === module.key }"
                :aria-selected="activeModule === module.key"
                @click="activeModule = module.key"
            >
                <span>{{ t(module.labelKey) }}</span>
                <Badge variant="neutral" class="tabular-nums">
                    {{ moduleSelectedCount(module.key) }}/{{ permissionsForModule(permissions, module.key).length }}
                </Badge>
            </button>
        </div>

        <p class="role-permissions-panel__description">
            {{ t(PERMISSION_MODULES.find((module) => module.key === activeModule)?.descriptionKey ?? '') }}
        </p>

        <div v-if="showSearch" class="role-permissions-panel__toolbar">
            <IndexToolbar>
                <Input
                    v-model="permissionSearch"
                    :placeholder="t('rolesPanel.searchPermissions')"
                    class="!w-full sm:!w-56"
                />
                <template #actions>
                    <Badge variant="neutral" class="tabular-nums">
                        {{ t('rolesPanel.selectedPermissions', { count: modelValue.length }) }}
                    </Badge>
                </template>
            </IndexToolbar>
        </div>

        <div class="role-permissions-panel__head">
            <p class="text-sm font-medium text-ink">{{ t(PERMISSION_MODULES.find((m) => m.key === activeModule)?.labelKey ?? '') }}</p>
            <label v-if="!disabled" class="flex items-center gap-2 text-sm text-ink-muted">
                <input
                    ref="selectAllRef"
                    type="checkbox"
                    :checked="allSelectedInModule"
                    class="rounded border-line text-accent focus:ring-accent"
                    @change="toggleAllInModule"
                />
                {{ t('forms.actions.selectAll') }}
            </label>
        </div>

        <div v-if="filteredPermissions.length" class="role-permissions-panel__grid">
            <label
                v-for="permission in filteredPermissions"
                :key="permission"
                class="role-permissions-panel__item"
                :class="modelValue.includes(permission) ? 'is-selected' : undefined"
            >
                <input
                    type="checkbox"
                    :checked="modelValue.includes(permission)"
                    :disabled="disabled"
                    class="rounded border-line text-accent focus:ring-accent disabled:opacity-60"
                    @change="togglePermission(permission)"
                />
                <span class="min-w-0 break-all">{{ permission }}</span>
            </label>
        </div>

        <p v-else class="role-permissions-panel__empty">
            {{ t('rolesPanel.noPermissionsMatch') }}
        </p>
    </div>
</template>
