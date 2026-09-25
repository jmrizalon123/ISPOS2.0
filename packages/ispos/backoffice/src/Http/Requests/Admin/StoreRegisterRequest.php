<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesRegisterFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterRequest extends FormRequest
{
    use ValidatesRegisterFields;

    public function authorize(): bool
    {
        return $this->user()?->can('registers.create') ?? false;
    }

    public function rules(): array
    {
        return $this->registerFieldRules(storeId: $this->input('store_id'));
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRegisterNullableFields();
        $this->mergeRegisterBooleanFields();
    }
}
