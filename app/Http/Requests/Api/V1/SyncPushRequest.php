<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.client_uuid' => ['required', 'uuid'],
            'sales.*.pos_shift_id' => ['required', 'string', 'exists:pos_shifts,id'],
            'sales.*.completed_at' => ['required', 'date'],
            'sales.*.subtotal' => ['required', 'numeric'],
            'sales.*.tax_total' => ['required', 'numeric'],
            'sales.*.discount_total' => ['nullable', 'numeric'],
            'sales.*.grand_total' => ['required', 'numeric'],
            'sales.*.customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'sales.*.promotion_id' => ['nullable', 'string', 'exists:promotions,id'],
            'sales.*.lines' => ['required', 'array', 'min:1'],
            'sales.*.lines.*.product_id' => ['required', 'string', 'exists:products,id'],
            'sales.*.lines.*.name' => ['required', 'string'],
            'sales.*.lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'sales.*.lines.*.unit_price' => ['required', 'numeric'],
            'sales.*.lines.*.line_subtotal' => ['required', 'numeric'],
            'sales.*.lines.*.line_total' => ['required', 'numeric'],
            'sales.*.payment.method' => ['nullable', 'string', 'max:50'],
            'sales.*.payment.amount' => ['nullable', 'numeric'],
        ];
    }
}
