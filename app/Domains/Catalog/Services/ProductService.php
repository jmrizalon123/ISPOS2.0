<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Domains\Inventory\Services\StoreInventoryService;
use App\Models\Product;
use App\Models\Store;
use App\Models\ProductBarcode;
use App\Models\ProductComponent;
use App\Models\ProductIngredient;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function __construct(
        protected ProductImageService $productImageService,
        protected StoreInventoryService $storeInventoryService,
    ) {}

    /**
     * @param  array{category_id?: string|null, brand_id?: string|null, status?: string|null, store_id?: string|null, sales_plan_id?: string|null, stock_status?: string|null}  $filters
     */
    public function paginate(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        array $filters = [],
        int $perPage = 15,
    ): LengthAwarePaginator {
        $categoryId = $filters['category_id'] ?? null;
        $brandId = $filters['brand_id'] ?? null;
        $status = $filters['status'] ?? null;
        $stockStatus = $filters['stock_status'] ?? null;
        $salesPlanId = $filters['sales_plan_id'] ?? null;
        $storeId = $filters['store_id'] ?? null;

        if (! $salesPlanId && $storeId) {
            $salesPlanId = Store::query()->whereKey($storeId)->value('sales_plan_id');
        }

        return $this->paginateCompanyCatalog(
            $user,
            Product::class,
            ['sku', 'name', 'description'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($categoryId, $brandId, $status, $stockStatus, $salesPlanId) {
                $q->with(['category:id,name,category_code', 'brand:id,name,brand_code', 'unit:id,name,symbol', 'salesPlan:id,name,plan_code'])
                    ->withSum('storeInventories as total_qty', 'qty')
                    ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                    ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
                    ->when($salesPlanId, fn ($query) => $query->where('sales_plan_id', $salesPlanId))
                    ->when($status, fn ($query) => $query->where('status', $status))
                    ->stockStatus($stockStatus)
                    ->orderBy('name');
            },
        );
    }

    /**
     * @param  list<\Illuminate\Http\UploadedFile>  $imageUploads
     */
    public function create(array $data, ?User $actor = null, array $imageUploads = []): Product
    {
        return DB::transaction(fn () => $this->createWithinTransaction($data, $actor, $imageUploads));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function createMany(array $data, ?User $actor = null)
    {
        return DB::transaction(function () use ($data, $actor) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $shared = [
                'company_id' => $data['company_id'],
                'sales_plan_id' => $data['sales_plan_id'],
                'product_type' => $data['product_type'],
                'tax_id' => $data['tax_id'] ?? null,
                'track_inventory' => (bool) ($data['track_inventory'] ?? false),
                'status' => $data['status'],
            ];

            return collect($items)->values()->map(function (array $item) use ($shared, $actor) {
                $payload = [
                    ...$shared,
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'cost' => $item['cost'] ?? 0,
                    'base_price' => $item['base_price'] ?? 0,
                    'ideal_qty' => $item['ideal_qty'] ?? null,
                    'warning_qty' => $item['warning_qty'] ?? null,
                    'category_id' => $item['category_id'] ?? null,
                    'brand_id' => $item['brand_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'has_variants' => (bool) ($item['has_variants'] ?? false),
                    'has_modifiers' => (bool) ($item['has_modifiers'] ?? false),
                    'has_components' => (bool) ($item['has_components'] ?? false),
                ];

                if (! empty($item['barcode'])) {
                    $payload['barcodes'] = [[
                        'barcode' => $item['barcode'],
                        'is_primary' => true,
                    ]];
                }

                return $this->createWithinTransaction(
                    [
                        ...$payload,
                        'variants' => $item['variants'] ?? [],
                        'modifier_groups' => $item['modifier_groups'] ?? [],
                        'components' => $item['components'] ?? [],
                        'ingredients' => $item['ingredients'] ?? [],
                        'images' => $item['images'] ?? [],
                    ],
                    $actor,
                    $item['image_uploads'] ?? [],
                );
            });
        });
    }

    /**
     * Create a product inside an existing database transaction.
     *
     * @param  list<\Illuminate\Http\UploadedFile>  $imageUploads
     */
    protected function createWithinTransaction(array $data, ?User $actor = null, array $imageUploads = []): Product
    {
        [$payload, $relations] = $this->extractProductPayload($data);

        $this->stampActor($payload, $actor);
        unset($payload['image']);
        $product = Product::create($payload);

        $this->syncProductRelations($product, $relations, $actor);
        $this->productImageService->sync($product, $relations['images'], $imageUploads, $actor);

        if ($product->track_inventory) {
            $this->storeInventoryService->initializeForProduct($product, $actor);
        }

        $this->logCatalogCreate('products', Product::class, $product->fresh(['variants', 'barcodes', 'prices', 'modifierGroups.options', 'components', 'ingredients', 'images']));

        return $product->fresh(['variants', 'barcodes', 'prices', 'modifierGroups.options', 'components.componentProduct', 'ingredients.ingredientProduct', 'images', 'category', 'brand', 'unit', 'tax']);
    }

    /**
     * @param  list<\Illuminate\Http\UploadedFile>  $imageUploads
     */
    public function update(Product $product, array $data, ?User $actor = null, array $imageUploads = []): Product
    {
        return DB::transaction(function () use ($product, $data, $actor, $imageUploads) {
            [$payload, $relations] = $this->extractProductPayload($data);

            if ($actor) {
                $payload['updated_by'] = $actor->id;
            }

            unset($payload['image']);
            $old = $product->toArray();
            $product->update($payload);

            $this->syncProductRelations($product, $relations, $actor);
            $this->productImageService->sync($product, $relations['images'], $imageUploads, $actor);

            if ($product->track_inventory) {
                $this->storeInventoryService->initializeForProduct($product->fresh(), $actor);
            }

            $this->logCatalogUpdate('products', Product::class, $product, $old);

            return $product->fresh(['variants', 'barcodes', 'prices', 'modifierGroups.options', 'components.componentProduct', 'ingredients.ingredientProduct', 'images', 'category', 'brand', 'unit', 'tax']);
        });
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    protected function extractProductPayload(array $data): array
    {
        $relations = [
            'variants' => $data['variants'] ?? [],
            'barcodes' => $data['barcodes'] ?? [],
            'prices' => $data['prices'] ?? [],
            'modifier_groups' => $data['modifier_groups'] ?? [],
            'components' => $data['components'] ?? [],
            'ingredients' => $data['ingredients'] ?? [],
            'images' => $data['images'] ?? [],
        ];

        unset(
            $data['variants'], $data['barcodes'], $data['prices'], $data['modifier_groups'],
            $data['components'], $data['ingredients'], $data['images'], $data['image_uploads'],
            $data['qty'],
        );

        return [$data, $relations];
    }

    /** @param  array<string, mixed>  $relations */
    protected function syncProductRelations(Product $product, array $relations, ?User $actor): void
    {
        $variantMap = $this->syncVariants($product, $relations['variants'], $actor);
        $this->syncBarcodes($product, $relations['barcodes'], $variantMap, $actor);
        $this->syncPrices($product, $relations['prices'], $variantMap);
        $this->syncModifierGroups($product, $relations['modifier_groups'], $actor);
        $this->syncComponents($product, $relations['components'], $actor);
        $this->syncIngredients($product, $relations['ingredients'], $actor);
    }

    public function delete(Product $product): void
    {
        $product->images()->get()->each(fn ($image) => $this->productImageService->deleteImage($image));

        $this->logCatalogDelete('products', Product::class, $product);
        $product->delete();
    }

    /** @param  list<array<string, mixed>>  $variants
     * @return array<string, string>
     */
    protected function syncVariants(Product $product, array $variants, ?User $actor): array
    {
        $map = [];
        $keepIds = [];

        foreach ($variants as $index => $row) {
            if (empty($row['variant_code']) || empty($row['name'])) {
                continue;
            }

            $payload = [
                'variant_code' => $row['variant_code'],
                'name' => $row['name'],
                'sku' => $row['sku'] ?? null,
                'cost' => $row['cost'] ?? 0,
                'selling_price' => $row['selling_price'] ?? 0,
                'sort_order' => $row['sort_order'] ?? $index,
                'status' => $row['status'] ?? 'active',
            ];

            if (! empty($row['id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }
                $variant->update($payload);
            } else {
                $payload['product_id'] = $product->id;
                if ($actor) {
                    $payload['created_by'] = $actor->id;
                    $payload['updated_by'] = $actor->id;
                }
                $variant = ProductVariant::create($payload);
            }

            $keepIds[] = $variant->id;
            $map[$row['client_key'] ?? $variant->id] = $variant->id;
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        return $map;
    }

    /** @param  list<array<string, mixed>>  $barcodes
     * @param  array<string, string>  $variantMap
     */
    protected function syncBarcodes(Product $product, array $barcodes, array $variantMap, ?User $actor): void
    {
        $keepIds = [];

        foreach ($barcodes as $row) {
            if (empty($row['barcode'])) {
                continue;
            }

            $variantId = null;
            if (! empty($row['product_variant_id'])) {
                $variantId = $row['product_variant_id'];
            } elseif (! empty($row['variant_client_key']) && isset($variantMap[$row['variant_client_key']])) {
                $variantId = $variantMap[$row['variant_client_key']];
            }

            $payload = [
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'barcode' => $row['barcode'],
                'is_primary' => (bool) ($row['is_primary'] ?? false),
            ];

            if (! empty($row['id'])) {
                $barcode = ProductBarcode::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }
                $barcode->update($payload);
                $keepIds[] = $barcode->id;
            } else {
                if ($actor) {
                    $payload['created_by'] = $actor->id;
                    $payload['updated_by'] = $actor->id;
                }
                $barcode = ProductBarcode::create($payload);
                $keepIds[] = $barcode->id;
            }
        }

        ProductBarcode::query()
            ->where('product_id', $product->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();
    }

    /** @param  list<array<string, mixed>>  $prices
     * @param  array<string, string>  $variantMap
     */
    protected function syncPrices(Product $product, array $prices, array $variantMap): void
    {
        ProductPrice::query()->where('product_id', $product->id)->delete();

        foreach ($prices as $row) {
            if (empty($row['price_group_id'])) {
                continue;
            }

            $variantId = null;
            if (! empty($row['product_variant_id'])) {
                $variantId = $row['product_variant_id'];
            } elseif (! empty($row['variant_client_key']) && isset($variantMap[$row['variant_client_key']])) {
                $variantId = $variantMap[$row['variant_client_key']];
            }

            ProductPrice::create([
                'price_group_id' => $row['price_group_id'],
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'price' => $row['price'] ?? 0,
            ]);
        }
    }

    /** @param  list<array<string, mixed>>  $groups */
    protected function syncModifierGroups(Product $product, array $groups, ?User $actor): void
    {
        if ($product->product_type !== 'menu_item' || ! $product->has_modifiers) {
            ProductModifierGroup::query()->where('product_id', $product->id)->delete();

            return;
        }

        $keepGroupIds = [];

        foreach ($groups as $index => $row) {
            if (empty($row['group_code']) || empty($row['name'])) {
                continue;
            }

            $groupPayload = [
                'group_code' => $row['group_code'],
                'name' => $row['name'],
                'selection_type' => $row['selection_type'] ?? 'single',
                'is_required' => (bool) ($row['is_required'] ?? false),
                'min_selections' => $row['min_selections'] ?? 0,
                'max_selections' => $row['max_selections'] ?? null,
                'sort_order' => $row['sort_order'] ?? $index,
                'status' => $row['status'] ?? 'active',
            ];

            if (! empty($row['id'])) {
                $group = ProductModifierGroup::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $groupPayload['updated_by'] = $actor->id;
                }
                $group->update($groupPayload);
            } else {
                $groupPayload['product_id'] = $product->id;
                if ($actor) {
                    $groupPayload['created_by'] = $actor->id;
                    $groupPayload['updated_by'] = $actor->id;
                }
                $group = ProductModifierGroup::create($groupPayload);
            }

            $keepGroupIds[] = $group->id;
            $this->syncModifierOptions($group, $row['options'] ?? [], $actor);
        }

        ProductModifierGroup::query()
            ->where('product_id', $product->id)
            ->when($keepGroupIds, fn ($q) => $q->whereNotIn('id', $keepGroupIds))
            ->delete();
    }

    /** @param  list<array<string, mixed>>  $options */
    protected function syncModifierOptions(ProductModifierGroup $group, array $options, ?User $actor): void
    {
        $keepIds = [];

        foreach ($options as $index => $row) {
            if (empty($row['option_code']) || empty($row['name'])) {
                continue;
            }

            $payload = [
                'option_code' => $row['option_code'],
                'name' => $row['name'],
                'price_adjustment' => $row['price_adjustment'] ?? 0,
                'is_default' => (bool) ($row['is_default'] ?? false),
                'sort_order' => $row['sort_order'] ?? $index,
                'status' => $row['status'] ?? 'active',
            ];

            if (! empty($row['id'])) {
                $option = ProductModifierOption::query()
                    ->where('product_modifier_group_id', $group->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }
                $option->update($payload);
            } else {
                $payload['product_modifier_group_id'] = $group->id;
                if ($actor) {
                    $payload['created_by'] = $actor->id;
                    $payload['updated_by'] = $actor->id;
                }
                $option = ProductModifierOption::create($payload);
            }

            $keepIds[] = $option->id;
        }

        ProductModifierOption::query()
            ->where('product_modifier_group_id', $group->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();
    }

    /** @param  list<array<string, mixed>>  $components */
    protected function syncComponents(Product $product, array $components, ?User $actor): void
    {
        if ($product->product_type !== 'menu_item' || ! $product->has_components) {
            ProductComponent::query()->where('product_id', $product->id)->delete();

            return;
        }

        $keepIds = [];

        foreach ($components as $index => $row) {
            if (empty($row['component_product_id'])) {
                continue;
            }

            if ($row['component_product_id'] === $product->id) {
                continue;
            }

            $payload = [
                'component_product_id' => $row['component_product_id'],
                'quantity' => $row['quantity'] ?? 1,
                'unit_id' => $row['unit_id'] ?? null,
                'is_optional' => (bool) ($row['is_optional'] ?? false),
                'sort_order' => $row['sort_order'] ?? $index,
                'notes' => $row['notes'] ?? null,
            ];

            if (! empty($row['id'])) {
                $component = ProductComponent::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }
                $component->update($payload);
            } else {
                $payload['product_id'] = $product->id;
                if ($actor) {
                    $payload['created_by'] = $actor->id;
                    $payload['updated_by'] = $actor->id;
                }
                $component = ProductComponent::create($payload);
            }

            $keepIds[] = $component->id;
        }

        ProductComponent::query()
            ->where('product_id', $product->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();
    }

    /** @param  list<array<string, mixed>>  $ingredients */
    protected function syncIngredients(Product $product, array $ingredients, ?User $actor): void
    {
        if ($product->product_type !== 'menu_item') {
            ProductIngredient::query()->where('product_id', $product->id)->delete();

            return;
        }

        $keepIds = [];

        foreach ($ingredients as $index => $row) {
            if (empty($row['ingredient_product_id'])) {
                continue;
            }

            $payload = [
                'ingredient_product_id' => $row['ingredient_product_id'],
                'quantity' => $row['quantity'] ?? 1,
                'unit_id' => $row['unit_id'] ?? null,
                'is_optional' => (bool) ($row['is_optional'] ?? false),
                'sort_order' => $row['sort_order'] ?? $index,
                'notes' => $row['notes'] ?? null,
            ];

            if (! empty($row['id'])) {
                $ingredient = ProductIngredient::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);
                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }
                $ingredient->update($payload);
            } else {
                $payload['product_id'] = $product->id;
                if ($actor) {
                    $payload['created_by'] = $actor->id;
                    $payload['updated_by'] = $actor->id;
                }
                $ingredient = ProductIngredient::create($payload);
            }

            $keepIds[] = $ingredient->id;
        }

        ProductIngredient::query()
            ->where('product_id', $product->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();
    }
}
