<?php

namespace Ispos\Backoffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:builtin,google'],
            'google_enabled' => ['boolean'],
            'google_use_widget' => ['boolean'],
            'google_api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
