<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receive', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'receipts' => ['required', 'array', 'min:1'],
            'receipts.*.line_id' => ['required', 'ulid', 'exists:purchase_order_lines,id'],
            'receipts.*.receive_qty' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
