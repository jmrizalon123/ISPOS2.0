<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loyalty.manage') ?? false;
    }

    public function rules(): array
    {
        $program = $this->route('loyalty_program');

        return [
            'program_code' => ['required', 'string', 'max:50', Rule::unique('loyalty_programs', 'program_code')->where(fn ($q) => $q->where('company_id', $program->company_id))->ignore($program->id)],
            'name' => ['required', 'string', 'max:255'],
            'earn_rate' => ['required', 'numeric', 'min:0'],
            'redeem_value_per_point' => ['nullable', 'numeric', 'min:0'],
            'min_redeem_points' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
