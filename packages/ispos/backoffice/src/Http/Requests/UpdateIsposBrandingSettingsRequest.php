<?php

namespace Ispos\Backoffice\Http\Requests;

use App\Services\IsposBrandingService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIsposBrandingSettingsRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'ownership' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.IsposBrandingService::MAX_LOGO_KILOBYTES],
            'remove_logo' => ['boolean'],
        ];
    }
}
