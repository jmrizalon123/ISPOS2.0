<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesUserProfileFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserProfileFields;

    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            ...$this->userProfileRules(),
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->hasGlobalOrganizationAccess()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }

        $this->merge([
            'base_type' => $this->input('base_type', 'store'),
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
            'is_locked' => filter_var($this->input('is_locked', false), FILTER_VALIDATE_BOOLEAN),
            'remove_avatar' => filter_var($this->input('remove_avatar', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
