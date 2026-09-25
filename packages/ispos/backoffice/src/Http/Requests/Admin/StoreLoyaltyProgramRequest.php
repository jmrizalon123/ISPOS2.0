<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyProgramRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('loyalty.manage') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'program_code' => ['required', 'string', 'max:50', Rule::unique('loyalty_programs', 'program_code')->where(fn ($q) => $q->where('company_id', $companyId))],
            'name' => ['required', 'string', 'max:255'],
            'earn_rate' => ['required', 'numeric', 'min:0'],
            'redeem_value_per_point' => ['nullable', 'numeric', 'min:0'],
            'min_redeem_points' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
