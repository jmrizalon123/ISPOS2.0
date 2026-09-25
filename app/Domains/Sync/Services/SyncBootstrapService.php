<?php

namespace App\Domains\Sync\Services;

use App\Domains\Sales\Services\PosCatalogService;
use App\Models\Category;
use App\Models\PosDevice;
use App\Models\ProductBarcode;
use App\Models\Tax;

class SyncBootstrapService
{
    public function __construct(
        protected PosCatalogService $catalog,
        protected SyncLogService $syncLogs,
    ) {}

    /** @return array<string, mixed> */
    public function bootstrap(PosDevice $device): array
    {
        $store = $device->store()->firstOrFail();
        $register = $device->register()->firstOrFail();

        $products = $this->catalog->listForStore($store)
            ->map(fn ($product) => $this->catalog->serializeProduct($product))
            ->values()
            ->all();

        $categories = Category::query()
            ->where('company_id', $device->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'category_code', 'name', 'parent_id'])
            ->map(fn ($category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
            ])
            ->values()
            ->all();

        $taxes = Tax::query()
            ->where('company_id', $device->company_id)
            ->where('status', 'active')
            ->orderBy('tax_code')
            ->get(['id', 'tax_code', 'name', 'rate', 'is_inclusive'])
            ->map(fn ($tax) => [
                'id' => $tax->id,
                'tax_code' => $tax->tax_code,
                'name' => $tax->name,
                'rate' => (string) $tax->rate,
                'is_inclusive' => $tax->is_inclusive,
            ])
            ->values()
            ->all();

        $barcodes = ProductBarcode::query()
            ->where('company_id', $device->company_id)
            ->orderBy('barcode')
            ->get(['barcode', 'product_id', 'product_variant_id'])
            ->map(fn ($row) => [
                'barcode' => $row->barcode,
                'product_id' => $row->product_id,
                'product_variant_id' => $row->product_variant_id,
            ])
            ->values()
            ->all();

        $generatedAt = now();

        $device->update([
            'last_bootstrap_at' => $generatedAt,
            'last_sync_at' => $generatedAt,
        ]);

        $this->syncLogs->record($device, 'bootstrap', 'success', count($products), [
            'categories' => count($categories),
            'taxes' => count($taxes),
            'barcodes' => count($barcodes),
        ]);

        return [
            'generated_at' => $generatedAt->toIso8601String(),
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
            ],
            'store' => [
                'id' => $store->id,
                'store_code' => $store->store_code,
                'store_name' => $store->store_name,
            ],
            'register' => [
                'id' => $register->id,
                'code' => $register->register_code,
                'name' => $register->register_name,
            ],
            'categories' => $categories,
            'taxes' => $taxes,
            'barcodes' => $barcodes,
            'products' => $products,
        ];
    }

    /** @return array<string, mixed> */
    public function pullCatalogChanges(PosDevice $device, ?string $since = null): array
    {
        $store = $device->store()->firstOrFail();
        $sinceTime = $since ? \Illuminate\Support\Carbon::parse($since) : null;

        $products = $this->catalog->listForStore($store)
            ->when($sinceTime, fn ($collection) => $collection->filter(
                fn ($product) => $product->updated_at?->gt($sinceTime),
            ))
            ->map(fn ($product) => $this->catalog->serializeProduct($product))
            ->values()
            ->all();

        $generatedAt = now();

        $device->update(['last_sync_at' => $generatedAt]);

        $this->syncLogs->record($device, 'pull', 'success', count($products));

        return [
            'generated_at' => $generatedAt->toIso8601String(),
            'products' => $products,
        ];
    }
}
