<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
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
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'ulid', 'exists:products,id'],
            'lines.*.ordered_qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }
}
