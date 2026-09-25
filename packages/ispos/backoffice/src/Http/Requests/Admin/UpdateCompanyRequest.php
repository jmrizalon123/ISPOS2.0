<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    use ValidatesCompanyFields;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('company')) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Company $company */
        $company = $this->route('company');

        return $this->companyFieldRules($company->id);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeBooleanFields();
    }

    private function mergeBooleanFields(): void
    {
        foreach ([
            'vat_registered',
            'enable_pos',
            'enable_inventory',
            'enable_accounting',
            'enable_hr',
            'enable_crm',
            'enable_ecommerce',
            'is_active',
        ] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
