<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLineComponent extends Model
{
    use HasUlids;

    protected $fillable = [
        'sale_line_id', 'component_product_id', 'component_name',
        'quantity', 'is_optional', 'included',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'is_optional' => 'boolean',
            'included' => 'boolean',
        ];
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class);
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
