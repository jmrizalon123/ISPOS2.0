<script setup lang="ts">
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmSave } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import {
    defaultJournalEntryForm,
    defaultJournalEntryLine,
    type JournalEntryFormData,
    type JournalEntryRecord,
} from '@/types/journalEntry';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const { can } = usePermissions();
const page = useModulePage('journalEntries');
const { t, hint, submit } = useLocale();

const props = defineProps<{
    entry: JournalEntryRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    accounts: Array<{ id: string; account_code: string; account_name: string; account_type: string }>;
}>();

const isEdit = computed(() => !!props.entry);
const isDraft = computed(() => !props.entry || props.entry.status === 'draft');
const isSystem = computed(() => !!props.entry?.source_type);
const pageTitle = computed(() => (isEdit.value ? props.entry!.entry_number : 'New journal entry'));

const form = useForm<JournalEntryFormData>(
    defaultJournalEntryForm(props.entry, props.companies[0]?.id ?? '', props.accounts[0]?.id ?? ''),
);

function addLine() {
    form.lines.push(defaultJournalEntryLine(props.accounts[0]?.id ?? ''));
}

function removeLine(index: number) {
    if (form.lines.length > 2) form.lines.splice(index, 1);
}

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.entry'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.journal-entries.update', props.entry!.id));
    } else {
        form.post(route('admin.journal-entries.store'));
    }
}

function postEntry() {
    router.post(route('admin.journal-entries.post', props.entry!.id));
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="journalEntries"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.journal-entries.index')"
        >
            <template v-if="entry" #meta>
                <Badge :variant="entry.status === 'posted' ? 'success' : 'neutral'">{{ entry.status }}</Badge>
            </template>

            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-6">
                    <FormSection title-key="entryHeader" description-key="entryHeader">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1 && !isEdit" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id" :disabled="!isDraft">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="entryDate" required :error="form.errors.entry_date">
                                <Input v-model="form.entry_date" type="date" :disabled="!isDraft || isSystem" />
                            </FormField>
                            <FormField label-key="description" class="md:col-span-2" :error="form.errors.description">
                                <FormTextarea v-model="form.description" :rows="2" :disabled="!isDraft || isSystem" />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title-key="entryLines" :description="hint('journalBalance')">
                        <div class="space-y-3">
                            <div v-for="(line, index) in form.lines" :key="index" class="grid gap-3 rounded-xl border border-border/60 p-4 md:grid-cols-12">
                                <FormField label-key="account" class="md:col-span-4" :error="form.errors[`lines.${index}.chart_of_account_id`]">
                                    <Select v-model="line.chart_of_account_id" :disabled="!isDraft || isSystem">
                                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                                            {{ account.account_code }} — {{ account.account_name }}
                                        </option>
                                    </Select>
                                </FormField>
                                <FormField label-key="lineDescription" class="md:col-span-3" :error="form.errors[`lines.${index}.description`]">
                                    <Input v-model="line.description" :disabled="!isDraft || isSystem" />
                                </FormField>
                                <FormField label-key="debit" class="md:col-span-2" :error="form.errors[`lines.${index}.debit`]">
                                    <Input v-model="line.debit" type="number" min="0" step="0.01" :disabled="!isDraft || isSystem" />
                                </FormField>
                                <FormField label-key="credit" class="md:col-span-2" :error="form.errors[`lines.${index}.credit`]">
                                    <Input v-model="line.credit" type="number" min="0" step="0.01" :disabled="!isDraft || isSystem" />
                                </FormField>
                                <div v-if="isDraft && !isSystem" class="flex items-end md:col-span-1">
                                    <Button type="button" variant="secondary" @click="removeLine(index)">{{ t('forms.actions.removeLine') }}</Button>
                                </div>
                            </div>
                            <Button v-if="isDraft && !isSystem" type="button" variant="secondary" @click="addLine">{{ t('forms.actions.addLine') }}</Button>
                            <p v-if="form.errors.lines" class="text-sm text-danger">{{ form.errors.lines }}</p>
                        </div>
                    </FormSection>
                </div>

                <div v-if="isEdit && isDraft && can('accounting.post') && !isSystem" class="flex justify-end px-6 pb-2">
                    <Button type="button" @click="postEntry">{{ t('common.post') }} {{ t('entities.entry') }}</Button>
                </div>

                <FormActionBar
                    v-if="isDraft && !isSystem"
                    :cancel-href="route('admin.journal-entries.index')"
                    :submit-label="isEdit ? submit('saveEntry') : submit('createEntry')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
