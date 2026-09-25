<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogUuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransferLine extends Model
{
    use HasCatalogUuid, HasUlids, SoftDeletes;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'uuid', 'stock_transfer_id', 'product_id', 'product_variant_id',
        'line_number', 'sku', 'barcode', 'product_name', 'unit_id', 'quantity',
        'requested_quantity', 'approved_quantity', 'released_quantity',
        'received_quantity', 'rejected_quantity', 'damaged_quantity',
        'unit_cost', 'total_cost', 'batch_no', 'serial_no', 'expiry_date',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:4',
            'approved_quantity' => 'decimal:4',
            'released_quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'damaged_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'expiry_date' => 'date',
        ];
    }

    public function getQuantityAttribute(): mixed
    {
        return $this->attributes['requested_quantity'] ?? $this->attributes['quantity'] ?? null;
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
