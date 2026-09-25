<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    use ValidatesCompanyFields;

    public function authorize(): bool
    {
        return $this->user()?->can('companies.create') ?? false;
    }

    public function rules(): array
    {
        return $this->companyFieldRules();
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
