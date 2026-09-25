<script setup lang="ts">
import RolePermissionsPanel from '@/Components/admin/roles/RolePermissionsPanel.vue';
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import Input from '@/Components/ui/Input.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = useModulePage('roles');
const { t, submit } = useLocale();

const props = defineProps<{
    role: { id: number; name: string; permissions: string[] } | null;
    permissions: string[];
}>();

const isEdit = computed(() => !!props.role);
const pageTitle = useFormPageTitle('role', isEdit);

const form = useForm({
    name: props.role?.name ?? '',
    permissions: props.role?.permissions ?? [],
});

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.role'),
    );
    if (!confirmed) return;

    if (isEdit.value) {
        form.put(route('admin.roles.update', props.role!.id));
    } else {
        form.post(route('admin.roles.store'));
    }
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="roles"
            :eyebrow="page.eyebrow"
            :back-href="role ? route('admin.roles.index', { role_id: role.id }) : route('admin.roles.index')"
        >
            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll">
                    <FormSection title-key="roleDetails" description-key="roleDetails">
                        <FormField label-key="name" :error="form.errors.name">
                            <Input v-model="form.name" :disabled="role?.name === 'Super Admin' || role?.name === 'Developer'" />
                        </FormField>

                        <div class="mt-4 overflow-hidden rounded-xl border border-line">
                            <RolePermissionsPanel
                                v-model="form.permissions"
                                :permissions="permissions"
                            />
                        </div>
                        <p v-if="form.errors.permissions" class="mt-2 text-sm text-danger">
                            {{ form.errors.permissions }}
                        </p>
                    </FormSection>
                </div>

                <FormActionBar
                    :cancel-href="role ? route('admin.roles.index', { role_id: role.id }) : route('admin.roles.index')"
                    :submit-label="isEdit ? submit('saveRole') : submit('createRole')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
