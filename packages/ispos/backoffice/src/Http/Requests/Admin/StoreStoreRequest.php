<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesStoreFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    use ValidatesStoreFields;

    public function authorize(): bool
    {
        return $this->user()?->can('stores.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return $this->storeFieldRules(companyId: $companyId);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->hasGlobalOrganizationAccess()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }

        $this->mergeStoreNullableFields();
        $this->mergeStoreBooleanFields();
    }
}
