<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProductInventory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()
            ->where('track_inventory', true)
            ->orderBy('id')
            ->each(function (Product $product) {
                $stores = Store::query()
                    ->where('company_id', $product->company_id)
                    ->where('status', 'active')
                    ->get();

                if ($stores->isEmpty()) {
                    return;
                }

                $mainStore = $stores->firstWhere('store_code', 'MAIN') ?? $stores->first();

                foreach ($stores as $store) {
                    $qty = $mainStore && $store->id === $mainStore->id ? $product->qty : 0;

                    StoreProductInventory::query()->firstOrCreate(
                        ['store_id' => $store->id, 'product_id' => $product->id],
                        ['company_id' => $product->company_id, 'qty' => $qty],
                    );
                }
            });
    }

    public function down(): void
    {
        // Non-destructive backfill; leave rows in place on rollback.
    }
};
