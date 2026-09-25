<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesOnlineStoreSettingFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreOnlineStoreSettingRequest extends FormRequest
{
    use ValidatesOnlineStoreSettingFields;

    public function authorize(): bool
    {
        return $this->user()?->can('online_store.manage') ?? false;
    }

    public function rules(): array
    {
        return $this->onlineStoreSettingFieldRules();
    }

    protected function prepareForValidation(): void
    {
        $this->mergeOnlineStoreSettingBooleans();
    }
}
