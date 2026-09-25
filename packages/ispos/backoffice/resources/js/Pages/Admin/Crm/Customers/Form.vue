<script setup lang="ts">
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
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
import { defaultCustomerForm, type CustomerFormData, type CustomerRecord } from '@/types/customer';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const { can } = usePermissions();
const page = useModulePage('customers');
const { t, submit } = useLocale();

const props = defineProps<{
    customer: CustomerRecord | null;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    loyaltyPrograms: Array<{ id: string; name: string; program_code: string }>;
    priceGroups: Array<{ id: string; name: string; group_code: string }>;
    membershipPlans: Array<{ id: string; name: string; plan_code: string }>;
}>();

const isEdit = computed(() => !!props.customer);
const pageTitle = useFormPageTitle('customer', isEdit);

const form = useForm<CustomerFormData>(defaultCustomerForm(props.customer, props.companies[0]?.id ?? ''));

const loyaltyForm = useForm({
    points_delta: '',
    notes: '',
});

const membershipForm = useForm({
    membership_plan_id: props.membershipPlans[0]?.id ?? '',
});

async function submitForm() {
    const confirmed = await confirmSave(
        isEdit.value ? t('common.save') : t('common.create'),
        t('entities.customer'),
    );
    if (!confirmed) return;
    if (isEdit.value) {
        form.put(route('admin.customers.update', props.customer!.id));
    } else {
        form.post(route('admin.customers.store'));
    }
}

function submitLoyaltyAdjust() {
    loyaltyForm.post(route('admin.customers.loyalty-adjust', props.customer!.id), {
        preserveScroll: true,
        onSuccess: () => loyaltyForm.reset(),
    });
}

function submitMembershipAssign() {
    membershipForm.post(route('admin.customers.membership.assign', props.customer!.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            subtitle-key="customers"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.customers.index')"
        >
            <template v-if="isEdit && customer" #meta>
                <Badge variant="neutral">{{ Number(customer.loyalty_points).toLocaleString() }} pts</Badge>
            </template>

            <form class="form-card-form" @submit.prevent="submitForm">
                <div class="form-card-scroll space-y-6">
                    <FormSection title-key="customerDetails" description-key="customerDetails">
                        <div class="form-grid">
                            <FormField v-if="companies.length > 1" label-key="company" required :error="form.errors.company_id">
                                <Select v-model="form.company_id">
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.display_name || company.name }}
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="code" required :error="form.errors.customer_code">
                                <Input v-model="form.customer_code" />
                            </FormField>
                            <FormField label-key="firstName" required :error="form.errors.first_name">
                                <Input v-model="form.first_name" />
                            </FormField>
                            <FormField label-key="lastName" :error="form.errors.last_name">
                                <Input v-model="form.last_name" />
                            </FormField>
                            <FormField label-key="email" :error="form.errors.email">
                                <Input v-model="form.email" type="email" />
                            </FormField>
                            <FormField label-key="phone" :error="form.errors.phone">
                                <Input v-model="form.phone" />
                            </FormField>
                            <FormField label-key="mobile" :error="form.errors.mobile">
                                <Input v-model="form.mobile" />
                            </FormField>
                            <FormField label-key="birthDate" :error="form.errors.birth_date">
                                <Input v-model="form.birth_date" type="date" />
                            </FormField>
                            <FormField label-key="priceGroup" :error="form.errors.price_group_id">
                                <Select v-model="form.price_group_id">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="group in priceGroups" :key="group.id" :value="group.id">
                                        {{ group.name }} ({{ group.group_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="loyaltyProgram" :error="form.errors.loyalty_program_id">
                                <Select v-model="form.loyalty_program_id">
                                    <option value="">{{ t('fields.none') }}</option>
                                    <option v-for="program in loyaltyPrograms" :key="program.id" :value="program.id">
                                        {{ program.name }} ({{ program.program_code }})
                                    </option>
                                </Select>
                            </FormField>
                            <FormField label-key="status" required :error="form.errors.status">
                                <Select v-model="form.status">
                                    <option value="active">{{ t('common.active') }}</option>
                                    <option value="inactive">{{ t('common.inactive') }}</option>
                                </Select>
                            </FormField>
                            <FormField label-key="addressLine1" class="md:col-span-2" :error="form.errors.address_line_1">
                                <Input v-model="form.address_line_1" />
                            </FormField>
                            <FormField label-key="city" :error="form.errors.city">
                                <Input v-model="form.city" />
                            </FormField>
                            <FormField label-key="province" :error="form.errors.province">
                                <Input v-model="form.province" />
                            </FormField>
                            <FormField label-key="postalCode" :error="form.errors.postal_code">
                                <Input v-model="form.postal_code" />
                            </FormField>
                            <FormField label-key="notes" class="md:col-span-2" :error="form.errors.notes">
                                <FormTextarea v-model="form.notes" :rows="2" />
                            </FormField>
                        </div>
                    </FormSection>

                    <template v-if="isEdit && customer">
                        <FormSection v-if="can('loyalty.manage')" :title="t('fields.loyaltyPoints')">
                            <p class="mb-4 text-sm text-ink-muted">
                                {{ t('fields.balanceAfter') }}:
                                <strong class="text-ink">{{ Number(customer.loyalty_points).toLocaleString() }}</strong>
                            </p>
                            <div class="form-grid">
                                <FormField label-key="pointsChange" required hint-key="pointsChange" :error="loyaltyForm.errors.points_delta">
                                    <Input v-model="loyaltyForm.points_delta" type="number" step="0.01" />
                                </FormField>
                                <FormField label-key="notes" class="md:col-span-2" :error="loyaltyForm.errors.notes">
                                    <FormTextarea v-model="loyaltyForm.notes" :rows="2" />
                                </FormField>
                            </div>
                            <div class="mt-4">
                                <Button type="button" :disabled="loyaltyForm.processing" @click="submitLoyaltyAdjust">
                                    Apply adjustment
                                </Button>
                            </div>
                        </FormSection>

                        <FormSection v-if="customer.loyalty_transactions?.length" :title="t('fields.loyaltyPoints')">
                            <div class="overflow-x-auto rounded-lg border border-line">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-surface-muted text-left text-xs uppercase text-ink-muted">
                                        <tr>
                                            <th class="px-3 py-2">{{ t('columns.date') }}</th>
                                            <th class="px-3 py-2">{{ t('columns.type') }}</th>
                                            <th class="px-3 py-2">{{ t('columns.delta') }}</th>
                                            <th class="px-3 py-2">{{ t('columns.balance') }}</th>
                                            <th class="px-3 py-2">{{ t('fields.notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="tx in customer.loyalty_transactions" :key="tx.id" class="border-t border-line">
                                            <td class="px-3 py-2 tabular-nums">{{ new Date(tx.created_at).toLocaleString() }}</td>
                                            <td class="px-3 py-2">{{ tx.transaction_type }}</td>
                                            <td class="px-3 py-2 tabular-nums">{{ Number(tx.points_delta) > 0 ? '+' : '' }}{{ Number(tx.points_delta).toLocaleString() }}</td>
                                            <td class="px-3 py-2 tabular-nums">{{ Number(tx.balance_after).toLocaleString() }}</td>
                                            <td class="px-3 py-2 text-ink-muted">{{ tx.notes ?? '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </FormSection>

                        <FormSection v-if="can('memberships.manage')" title-key="membershipDetails">
                            <div class="form-grid">
                                <FormField label-key="membershipPlan" required :error="membershipForm.errors.membership_plan_id">
                                    <Select v-model="membershipForm.membership_plan_id">
                                        <option v-for="plan in membershipPlans" :key="plan.id" :value="plan.id">
                                            {{ plan.name }} ({{ plan.plan_code }})
                                        </option>
                                    </Select>
                                </FormField>
                            </div>
                            <div class="mt-4">
                                <Button type="button" :disabled="membershipForm.processing || !membershipPlans.length" @click="submitMembershipAssign">
                                    Assign membership
                                </Button>
                            </div>
                        </FormSection>

                        <FormSection v-if="customer.memberships?.length" title-key="membershipDetails">
                            <div class="overflow-x-auto rounded-lg border border-line">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-surface-muted text-left text-xs uppercase text-ink-muted">
                                        <tr>
                                            <th class="px-3 py-2">{{ t('fields.name') }}</th>
                                            <th class="px-3 py-2">{{ t('fields.status') }}</th>
                                            <th class="px-3 py-2">{{ t('columns.opened') }}</th>
                                            <th class="px-3 py-2">{{ t('fields.durationDays') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="membership in customer.memberships" :key="membership.id" class="border-t border-line">
                                            <td class="px-3 py-2">{{ membership.membership_plan?.name ?? '—' }}</td>
                                            <td class="px-3 py-2">
                                                <Badge :variant="membership.status === 'active' ? 'success' : 'neutral'">{{ membership.status }}</Badge>
                                            </td>
                                            <td class="px-3 py-2 tabular-nums">{{ new Date(membership.started_at).toLocaleDateString() }}</td>
                                            <td class="px-3 py-2 tabular-nums">{{ membership.expires_at ? new Date(membership.expires_at).toLocaleDateString() : '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </FormSection>
                    </template>
                </div>

                <FormActionBar
                    :cancel-href="route('admin.customers.index')"
                    :submit-label="isEdit ? submit('saveCustomer') : submit('createCustomer')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
