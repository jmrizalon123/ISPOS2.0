<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesStoreFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    use ValidatesStoreFields;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Store $store */
        $store = $this->route('store');
        $companyId = $this->input('company_id', $store->company_id);

        return $this->storeFieldRules($store->id, $companyId);
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
