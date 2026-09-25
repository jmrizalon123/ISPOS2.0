<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesOnlineStoreSettingFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOnlineStoreSettingRequest extends FormRequest
{
    use ValidatesOnlineStoreSettingFields;

    public function authorize(): bool
    {
        $setting = $this->route('online_store_setting');

        return $this->user()?->can('update', $setting) ?? false;
    }

    public function rules(): array
    {
        $setting = $this->route('online_store_setting');

        return $this->onlineStoreSettingFieldRules($setting?->id);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeOnlineStoreSettingBooleans();
    }
}
