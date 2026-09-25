<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesRegisterFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegisterRequest extends FormRequest
{
    use ValidatesRegisterFields;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('register')) ?? false;
    }

    public function rules(): array
    {
        $register = $this->route('register');
        $storeId = $this->input('store_id', $register->store_id);

        return $this->registerFieldRules($register->id, $storeId);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRegisterNullableFields();
        $this->mergeRegisterBooleanFields();
    }
}
