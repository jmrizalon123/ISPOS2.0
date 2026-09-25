<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\SalesPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesPlanRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('sales_plan')) ?? false;
    }

    public function rules(): array
    {
        /** @var SalesPlan $salesPlan */
        $salesPlan = $this->route('sales_plan');
        $companyId = $this->input('company_id', $salesPlan->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'plan_code' => ['required', 'string', 'max:50', Rule::unique('sales_plans', 'plan_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($salesPlan->id)],
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
