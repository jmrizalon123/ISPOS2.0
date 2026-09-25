<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'sales_plan_id', 'sku', 'name', 'description', 'product_type',
        'category_id', 'brand_id', 'unit_id', 'tax_id',
        'cost', 'base_price', 'track_inventory', 'qty', 'ideal_qty', 'warning_qty',
        'has_variants', 'has_modifiers', 'has_components', 'image', 'status',
        'created_by', 'updated_by',
    ];

    protected $appends = ['stock_status'];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
            'base_price' => 'decimal:4',
            'track_inventory' => 'boolean',
            'qty' => 'decimal:4',
            'ideal_qty' => 'decimal:4',
            'warning_qty' => 'decimal:4',
            'has_variants' => 'boolean',
            'has_modifiers' => 'boolean',
            'has_components' => 'boolean',
        ];
    }

    /** @param  Builder<Product>  $query */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->where(function (Builder $inner) {
                $inner->where('qty', '<=', 0)
                    ->orWhere(function (Builder $threshold) {
                        $threshold->whereNotNull('warning_qty')
                            ->whereColumn('qty', '<=', 'warning_qty');
                    });
            });
    }

    /** @param  Builder<Product>  $query */
    public function scopeStockStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') {
            return $query;
        }

        return match ($status) {
            'low' => $query->lowStock()->where('qty', '>', 0),
            'out' => $query->where('track_inventory', true)
                ->where('status', 'active')
                ->where('qty', '<=', 0),
            'ok' => $query->where('track_inventory', true)
                ->where('status', 'active')
                ->where('qty', '>', 0)
                ->where(function (Builder $inner) {
                    $inner->whereNull('warning_qty')
                        ->orWhereColumn('qty', '>', 'warning_qty');
                }),
            default => $query,
        };
    }

    public function getStockStatusAttribute(): string
    {
        if (! $this->track_inventory) {
            return 'not_tracked';
        }

        if ((float) $this->qty <= 0) {
            return 'out';
        }

        if ($this->warning_qty !== null && (float) $this->qty <= (float) $this->warning_qty) {
            return 'low';
        }

        return 'ok';
    }

    public function isLowStock(): bool
    {
        return in_array($this->stock_status, ['low', 'out'], true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesPlan(): BelongsTo
    {
        return $this->belongsTo(SalesPlan::class);
    }

    /** @param  Builder<Product>  $query */
    public function scopeForStore(Builder $query, Store $store): Builder
    {
        return $query
            ->where('company_id', $store->company_id)
            ->when(
                $store->sales_plan_id,
                fn (Builder $q) => $q->where('sales_plan_id', $store->sales_plan_id),
                fn (Builder $q) => $q->whereNull('sales_plan_id'),
            );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function modifierGroups(): HasMany
    {
        return $this->hasMany(ProductModifierGroup::class)->orderBy('sort_order');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class)->orderBy('sort_order');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class)->orderBy('sort_order');
    }

    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'ingredient_product_id');
    }

    public function usedAsComponent(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id');
    }

    public function storeInventories(): HasMany
    {
        return $this->hasMany(StoreProductInventory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function defaultImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_default', true);
    }

    public function getImageAttribute(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://') && ! str_starts_with($value, '/')) {
            return \App\Support\PublicStorage::url($value);
        }

        return $value;
    }
}
