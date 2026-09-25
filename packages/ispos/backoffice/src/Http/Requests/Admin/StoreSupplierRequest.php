<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'supplier_code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'supplier_code')->where(fn ($q) => $q->where('company_id', $companyId))],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
