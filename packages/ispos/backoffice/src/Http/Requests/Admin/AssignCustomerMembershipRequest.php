<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignCustomerMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('memberships.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'membership_plan_id' => ['required', 'ulid', 'exists:membership_plans,id'],
        ];
    }
}
