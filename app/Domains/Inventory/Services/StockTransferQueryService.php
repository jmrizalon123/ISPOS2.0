<?php

namespace App\Domains\Inventory\Services;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockTransferQueryService
{
    /**
     * @param  array{company_id?: string|null, store_id?: string|null, search?: string|null}  $filters
     */
    public function paginate(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = StockTransfer::query()
            ->with([
                'fromStore:id,store_name,store_code',
                'toStore:id,store_name,store_code',
                'fromWarehouse:id,store_name,store_code',
                'toWarehouse:id,store_name,store_code',
            ])
            ->withCount('lines')
            ->orderByDesc('transfer_date')
            ->orderByDesc('created_at');

        if ($user->hasGlobalOrganizationAccess()) {
            if (! empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
        } else {
            $query->where('company_id', $user->company_id);

            if (! $user->hasRole('Company Admin')) {
                $storeIds = $user->stores()->pluck('stores.id');
                $query->where(function ($inner) use ($storeIds) {
                    $inner->whereIn('from_store_id', $storeIds)
                        ->orWhereIn('to_store_id', $storeIds)
                        ->orWhereIn('from_warehouse_id', $storeIds)
                        ->orWhereIn('to_warehouse_id', $storeIds);
                });
            }
        }

        if (! empty($filters['store_id'])) {
            $storeId = $filters['store_id'];
            $query->where(function ($inner) use ($storeId) {
                $inner->where('from_store_id', $storeId)
                    ->orWhere('to_store_id', $storeId)
                    ->orWhere('from_warehouse_id', $storeId)
                    ->orWhere('to_warehouse_id', $storeId);
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($inner) use ($search) {
                $inner->where('transfer_no', 'like', "%{$search}%")
                    ->orWhere('transfer_number', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->through(fn (StockTransfer $transfer) => [
            'id' => $transfer->id,
            'transfer_no' => $transfer->transfer_no ?: $transfer->transfer_number,
            'transfer_number' => $transfer->transfer_no ?: $transfer->transfer_number,
            'transfer_type' => $transfer->transfer_type,
            'priority' => $transfer->priority,
            'status' => $transfer->status,
            'notes' => $transfer->notes,
            'transfer_date' => $transfer->transfer_date?->toDateString(),
            'transferred_at' => $transfer->received_at?->toIso8601String() ?? $transfer->transferred_at?->toIso8601String(),
            'line_count' => $transfer->total_items ?: $transfer->lines_count,
            'from_store' => $this->locationPayload($transfer->fromStore),
            'to_store' => $this->locationPayload($transfer->toStore),
            'from_warehouse' => $this->locationPayload($transfer->fromWarehouse),
            'to_warehouse' => $this->locationPayload($transfer->toWarehouse),
        ]);
    }

    /** @return array<string, mixed> */
    public function detail(StockTransfer $transfer): array
    {
        $transfer->load([
            'fromStore', 'toStore', 'fromWarehouse', 'toWarehouse',
            'requestedBy:id,name', 'approvedBy:id,name', 'releasedBy:id,name',
            'receivedBy:id,name', 'cancelledBy:id,name',
            'lines.product:id,sku,name',
        ]);

        return [
            'id' => $transfer->id,
            'uuid' => $transfer->uuid,
            'transfer_no' => $transfer->transfer_no ?: $transfer->transfer_number,
            'transfer_number' => $transfer->transfer_no ?: $transfer->transfer_number,
            'transfer_type' => $transfer->transfer_type,
            'priority' => $transfer->priority,
            'status' => $transfer->status,
            'reason' => $transfer->reason,
            'reference_no' => $transfer->reference_no,
            'notes' => $transfer->notes,
            'transfer_date' => $transfer->transfer_date?->toDateString(),
            'requested_date' => $transfer->requested_date?->toDateString(),
            'expected_date' => $transfer->expected_date?->toDateString(),
            'transferred_at' => $transfer->received_at?->toIso8601String() ?? $transfer->transferred_at?->toIso8601String(),
            'approved_at' => $transfer->approved_at?->toIso8601String(),
            'released_at' => $transfer->released_at?->toIso8601String(),
            'received_at' => $transfer->received_at?->toIso8601String(),
            'cancelled_at' => $transfer->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $transfer->cancellation_reason,
            'total_items' => $transfer->total_items,
            'total_quantity' => (string) $transfer->total_quantity,
            'from_store' => $this->locationPayload($transfer->fromStore),
            'to_store' => $this->locationPayload($transfer->toStore),
            'from_warehouse' => $this->locationPayload($transfer->fromWarehouse),
            'to_warehouse' => $this->locationPayload($transfer->toWarehouse),
            'requested_by' => $transfer->requestedBy?->name,
            'approved_by' => $transfer->approvedBy?->name,
            'released_by' => $transfer->releasedBy?->name,
            'received_by' => $transfer->receivedBy?->name,
            'cancelled_by' => $transfer->cancelledBy?->name,
            'lines' => $transfer->lines->map(fn ($line) => [
                'id' => $line->id,
                'line_number' => $line->line_number,
                'sku' => $line->sku ?: $line->product?->sku,
                'barcode' => $line->barcode,
                'product_name' => $line->product_name ?: $line->product?->name,
                'requested_quantity' => (string) $line->requested_quantity,
                'approved_quantity' => (string) $line->approved_quantity,
                'released_quantity' => (string) $line->released_quantity,
                'received_quantity' => (string) $line->received_quantity,
                'rejected_quantity' => (string) $line->rejected_quantity,
                'damaged_quantity' => (string) $line->damaged_quantity,
                'unit_cost' => $line->unit_cost !== null ? (string) $line->unit_cost : null,
                'total_cost' => $line->total_cost !== null ? (string) $line->total_cost : null,
                'batch_no' => $line->batch_no,
                'serial_no' => $line->serial_no,
                'expiry_date' => $line->expiry_date?->toDateString(),
                'notes' => $line->notes,
                'status' => $line->status,
                'quantity' => (string) $line->requested_quantity,
                'product' => [
                    'id' => $line->product?->id,
                    'sku' => $line->sku ?: $line->product?->sku,
                    'name' => $line->product_name ?: $line->product?->name,
                ],
            ])->values()->all(),
        ];
    }

    /** @return array{id: string, store_name: string, store_code: string}|null */
    protected function locationPayload($store): ?array
    {
        if (! $store) {
            return null;
        }

        return [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'store_code' => $store->store_code,
        ];
    }
}
