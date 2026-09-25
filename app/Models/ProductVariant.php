<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasAuditUsers, HasCatalogUuid, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'product_id', 'variant_code', 'name', 'sku',
        'cost', 'selling_price', 'sort_order', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
