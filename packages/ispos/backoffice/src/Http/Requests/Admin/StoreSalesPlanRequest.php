<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesPlanRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('sales_plans.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'plan_code' => ['required', 'string', 'max:50', Rule::unique('sales_plans', 'plan_code')->where(fn ($q) => $q->where('company_id', $companyId))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => [
                'ulid',
                Rule::exists('stores', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
        $this->merge([
            'store_ids' => array_values(array_filter((array) $this->input('store_ids', []))),
        ]);
    }
}
