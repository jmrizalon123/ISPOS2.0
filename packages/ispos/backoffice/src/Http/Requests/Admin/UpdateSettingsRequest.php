<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in([
                Setting::SCOPE_SYSTEM,
                Setting::SCOPE_COMPANY,
                Setting::SCOPE_STORE,
                Setting::SCOPE_REGISTER,
            ])],
            'scope_id' => ['nullable', 'ulid'],
            'settings' => ['required', 'array'],
            'settings.receipt_footer' => ['nullable', 'string', 'max:500'],
            'settings.tax_inclusive' => ['nullable', 'boolean'],
            'settings.pos_theme' => ['nullable', Rule::in(['light', 'dark', 'high-contrast'])],
            'settings.currency_symbol' => ['nullable', 'string', 'max:5'],
        ];
    }
}
