<?php

namespace App\Domains\Inventory\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentService
{
    public function __construct(
        protected StockMovementService $stockMovement,
        protected AuditLogger $auditLogger,
    ) {}

    public function adjust(
        User $user,
        Store $store,
        Product $product,
        float $quantityDelta,
        string $reason,
        ?string $notes = null,
    ): void {
        if ($quantityDelta == 0) {
            throw ValidationException::withMessages(['quantity_delta' => 'Adjustment quantity cannot be zero.']);
        }

        if ($store->company_id !== $product->company_id) {
            throw ValidationException::withMessages(['product_id' => 'Product does not belong to the store company.']);
        }

        if (! $product->track_inventory) {
            throw ValidationException::withMessages(['product_id' => 'Product does not track inventory.']);
        }

        $movement = $this->stockMovement->record(
            $store->company_id,
            $store->id,
            $product->id,
            'adjustment',
            (string) $quantityDelta,
            $user,
            [
                'reason' => $reason,
                'notes' => $notes,
            ],
        );

        $this->auditLogger->log(
            'adjust',
            'inventory',
            Product::class,
            $product->id,
            null,
            [
                'store_id' => $store->id,
                'quantity_delta' => $quantityDelta,
                'reason' => $reason,
                'movement_id' => $movement->id,
            ],
            $user,
        );
    }
}
