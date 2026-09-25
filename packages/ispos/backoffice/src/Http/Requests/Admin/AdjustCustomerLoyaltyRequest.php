<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustCustomerLoyaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loyalty.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'points_delta' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
