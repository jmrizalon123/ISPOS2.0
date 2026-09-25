<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.adjust') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'transfer_type' => $this->input('transfer_type') ?: 'store_to_store',
            'priority' => $this->input('priority') ?: 'normal',
        ]);
    }

    public function rules(): array
    {
        $type = $this->input('transfer_type', 'store_to_store');

        $fromStore = $this->requiresStore($type, 'from')
            ? ['required', 'string', 'exists:stores,id']
            : ['nullable', 'string', 'exists:stores,id'];
        $toStore = $this->requiresStore($type, 'to')
            ? ['required', 'string', 'exists:stores,id']
            : ['nullable', 'string', 'exists:stores,id'];
        $fromWarehouse = $this->requiresWarehouse($type, 'from')
            ? ['required', 'string', 'exists:stores,id']
            : ['nullable', 'string', 'exists:stores,id'];
        $toWarehouse = $this->requiresWarehouse($type, 'to')
            ? ['required', 'string', 'exists:stores,id']
            : ['nullable', 'string', 'exists:stores,id'];

        if ($type === 'store_to_store') {
            $toStore[] = 'different:from_store_id';
        }

        return [
            'transfer_type' => ['required', 'string', Rule::in(StockTransfer::TYPES)],
            'priority' => ['required', 'string', Rule::in(StockTransfer::PRIORITIES)],
            'from_store_id' => $fromStore,
            'to_store_id' => $toStore,
            'from_warehouse_id' => $fromWarehouse,
            'to_warehouse_id' => $toWarehouse,
            'transfer_date' => ['nullable', 'date'],
            'requested_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'exists:products,id', 'distinct'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.requested_quantity' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:80'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:80'],
            'lines.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->sourceLocationId();
            $to = $this->destinationLocationId();

            if ($from && $to && $from === $to) {
                $validator->errors()->add('to_store_id', 'Destination must be different from source.');
            }
        });
    }

    public function sourceLocationId(): ?string
    {
        return $this->requiresWarehouse($this->input('transfer_type'), 'from')
            ? $this->input('from_warehouse_id')
            : $this->input('from_store_id');
    }

    public function destinationLocationId(): ?string
    {
        return $this->requiresWarehouse($this->input('transfer_type'), 'to')
            ? $this->input('to_warehouse_id')
            : $this->input('to_store_id');
    }

    protected function requiresStore(?string $type, string $side): bool
    {
        return match ($type) {
            'warehouse_to_store' => $side === 'to',
            'store_to_warehouse' => $side === 'from',
            'warehouse_to_warehouse' => false,
            default => true,
        };
    }

    protected function requiresWarehouse(?string $type, string $side): bool
    {
        return match ($type) {
            'warehouse_to_store' => $side === 'from',
            'store_to_warehouse' => $side === 'to',
            'warehouse_to_warehouse' => true,
            default => false,
        };
    }
}
