<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\Tax;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tax')) ?? false;
    }

    public function rules(): array
    {
        /** @var Tax $tax */
        $tax = $this->route('tax');
        $companyId = $this->input('company_id', $tax->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'tax_code' => ['required', 'string', 'max:50', Rule::unique('taxes', 'tax_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($tax->id)],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_inclusive' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
        $this->merge(['is_inclusive' => $this->boolean('is_inclusive')]);
    }
}
