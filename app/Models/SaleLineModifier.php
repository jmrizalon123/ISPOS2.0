<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLineModifier extends Model
{
    use HasUlids;

    protected $fillable = [
        'sale_line_id', 'product_modifier_group_id', 'product_modifier_option_id',
        'modifier_group_name', 'option_name', 'price_adjustment',
    ];

    protected function casts(): array
    {
        return ['price_adjustment' => 'decimal:4'];
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class);
    }
}
