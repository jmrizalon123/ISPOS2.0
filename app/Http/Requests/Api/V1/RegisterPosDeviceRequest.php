<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPosDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.access') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'register_id' => ['required', 'string', 'exists:registers,id'],
            'name' => ['required', 'string', 'max:120'],
            'fingerprint' => ['nullable', 'string', 'max:191'],
        ];
    }
}
