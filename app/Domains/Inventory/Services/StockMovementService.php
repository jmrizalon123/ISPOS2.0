<?php

namespace App\Domains\Inventory\Services;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function __construct(protected StoreInventoryService $storeInventory) {}

    /**
     * @param  array{sale_id?: string|null, sale_line_id?: string|null, reference_type?: string|null, reference_id?: string|null, reversal_of_id?: string|null, reason?: string|null, notes?: string|null}  $context
     */
    public function record(
        string $companyId,
        string $storeId,
        string $productId,
        string $movementType,
        string $quantityDelta,
        ?User $user = null,
        array $context = [],
    ): StockMovement {
        return DB::transaction(function () use ($companyId, $storeId, $productId, $movementType, $quantityDelta, $user, $context) {
            $inventory = $this->storeInventory->getOrCreate($companyId, $storeId, $productId, $user);
            $qtyBefore = (string) $inventory->qty;
            $qtyAfter = bcadd($qtyBefore, $quantityDelta, 4);

            $this->storeInventory->setBalance($inventory, $qtyAfter, $user);

            return StockMovement::create([
                'company_id' => $companyId,
                'store_id' => $storeId,
                'product_id' => $productId,
                'movement_type' => $movementType,
                'quantity_delta' => $quantityDelta,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'sale_id' => $context['sale_id'] ?? null,
                'sale_line_id' => $context['sale_line_id'] ?? null,
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id' => $context['reference_id'] ?? null,
                'reversal_of_id' => $context['reversal_of_id'] ?? null,
                'reason' => $context['reason'] ?? null,
                'notes' => $context['notes'] ?? null,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);
        });
    }
}
