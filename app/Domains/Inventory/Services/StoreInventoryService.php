<?php

namespace App\Domains\Inventory\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;

class StoreInventoryService
{
    public function getBalance(string $storeId, string $productId): string
    {
        $row = StoreProductInventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->value('qty');

        return (string) ($row ?? '0.0000');
    }

    public function getOrCreate(string $companyId, string $storeId, string $productId, ?User $user = null): StoreProductInventory
    {
        return StoreProductInventory::query()->firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId],
            [
                'company_id' => $companyId,
                'qty' => 0,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ],
        );
    }

    public function initializeForProduct(Product $product, ?User $user = null): void
    {
        if (! $product->track_inventory) {
            return;
        }

        Store::query()
            ->where('company_id', $product->company_id)
            ->where('status', 'active')
            ->when(
                $product->sales_plan_id,
                fn ($q) => $q->where('sales_plan_id', $product->sales_plan_id),
                fn ($q) => $q->whereNull('sales_plan_id'),
            )
            ->pluck('id')
            ->each(fn (string $storeId) => $this->getOrCreate(
                $product->company_id,
                $storeId,
                $product->id,
                $user,
            ));
    }

    public function setBalance(
        StoreProductInventory $inventory,
        string $newQty,
        ?User $user = null,
    ): StoreProductInventory {
        $inventory->update([
            'qty' => $newQty,
            'updated_by' => $user?->id,
        ]);

        return $inventory->fresh();
    }

    /** Seed store inventory from legacy products.qty for MAIN store. */
    public function migrateLegacyQty(Product $product, Store $mainStore, ?User $user = null): void
    {
        if (! $product->track_inventory) {
            return;
        }

        $inventory = $this->getOrCreate($product->company_id, $mainStore->id, $product->id, $user);
        $inventory->update([
            'qty' => $product->qty,
            'updated_by' => $user?->id,
        ]);
    }
}
