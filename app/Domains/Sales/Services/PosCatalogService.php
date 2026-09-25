<?php

namespace App\Domains\Sales\Services;

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductModifierOption;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Collection;

class PosCatalogService
{
    public function __construct(protected PosPricingService $pricing) {}

    /** @return Collection<int, Product> */
    public function listForStore(Store $store, ?string $search = null, ?string $categoryId = null): Collection
    {
        return Product::query()
            ->with([
                'category:id,name,category_code',
                'tax',
                'variants' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'modifierGroups.options' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'components.componentProduct:id,name,sku,image',
            ])
            ->forStore($store)
            ->where('status', 'active')
            ->whereIn('product_type', ['retail', 'menu_item'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->each(fn (Product $p) => $this->attachPosMeta($p, $store));
    }

    public function findForStore(Store $store, string $productId): ?Product
    {
        $product = Product::query()
            ->with([
                'tax',
                'variants' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'modifierGroups.options' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'components.componentProduct:id,name,sku,image',
            ])
            ->forStore($store)
            ->where('status', 'active')
            ->whereIn('product_type', ['retail', 'menu_item'])
            ->find($productId);

        return $product ? $this->attachPosMeta($product, $store) : null;
    }

    public function lookupBarcode(Store $store, string $barcode): ?Product
    {
        $record = ProductBarcode::query()
            ->with('product.tax', 'variant')
            ->where('company_id', $store->company_id)
            ->where('barcode', $barcode)
            ->first();

        if (! $record?->product || $record->product->status !== 'active') {
            return null;
        }

        if (! in_array($record->product->product_type, ['retail', 'menu_item'], true)) {
            return null;
        }

        $product = $this->findForStore($store, $record->product_id);
        if ($product && $record->product_variant_id) {
            $product->setAttribute('matched_variant_id', $record->product_variant_id);
        }

        return $product;
    }

    protected function attachPosMeta(Product $product, Store $store): Product
    {
        $product->setAttribute('pos_unit_price', $this->pricing->resolveUnitPrice($product, $store));

        $product->variants->each(function (ProductVariant $variant) use ($product, $store) {
            $variant->setAttribute('pos_unit_price', $this->pricing->resolveUnitPrice($product, $store, $variant));
        });

        return $product;
    }

    /** @return array<string, mixed> */
    public function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'image' => $product->image,
            'product_type' => $product->product_type,
            'category_id' => $product->category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'has_variants' => $product->has_variants,
            'has_modifiers' => $product->has_modifiers,
            'has_components' => $product->has_components,
            'pos_unit_price' => $product->getAttribute('pos_unit_price'),
            'matched_variant_id' => $product->getAttribute('matched_variant_id'),
            'variants' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'sku' => $v->sku,
                'pos_unit_price' => $v->getAttribute('pos_unit_price'),
            ])->values()->all(),
            'modifier_groups' => $product->modifierGroups
                ->where('status', 'active')
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'group_code' => $g->group_code,
                    'selection_type' => $g->selection_type,
                    'is_required' => $g->is_required,
                    'min_selections' => $g->min_selections,
                    'max_selections' => $g->max_selections,
                    'options' => $g->options->where('status', 'active')->map(fn ($o) => [
                        'id' => $o->id,
                        'name' => $o->name,
                        'option_code' => $o->option_code,
                        'price_adjustment' => (string) $o->price_adjustment,
                        'is_default' => $o->is_default,
                    ])->values()->all(),
                ])->values()->all(),
            'components' => $product->components->map(fn ($c) => [
                'id' => $c->id,
                'component_product_id' => $c->component_product_id,
                'name' => $c->componentProduct?->name ?? 'Component',
                'quantity' => (string) $c->quantity,
                'is_optional' => $c->is_optional,
            ])->values()->all(),
        ];
    }

    /** @param  list<string>  $optionIds */
    public function resolveModifierOptions(array $optionIds): Collection
    {
        return ProductModifierOption::query()
            ->with('modifierGroup')
            ->whereIn('id', $optionIds)
            ->where('status', 'active')
            ->get();
    }
}
