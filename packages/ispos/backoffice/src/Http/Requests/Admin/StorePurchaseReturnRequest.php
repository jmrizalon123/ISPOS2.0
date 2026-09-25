<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'ulid', 'exists:stores,id'],
            'supplier_id' => ['required', 'ulid', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'ulid', 'exists:purchase_orders,id'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'ulid', 'exists:products,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'lines.*.purchase_order_line_id' => ['nullable', 'ulid', 'exists:purchase_order_lines,id'],
        ];
    }
}
