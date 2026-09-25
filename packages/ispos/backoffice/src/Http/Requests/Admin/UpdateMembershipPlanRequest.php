<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('memberships.manage') ?? false;
    }

    public function rules(): array
    {
        $plan = $this->route('membership_plan');

        return [
            'plan_code' => ['required', 'string', 'max:50', Rule::unique('membership_plans', 'plan_code')->where(fn ($q) => $q->where('company_id', $plan->company_id))->ignore($plan->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'price_group_id' => ['nullable', 'ulid', 'exists:price_groups,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
