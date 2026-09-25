<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModifierOption extends Model
{
    use HasAuditUsers, HasCatalogUuid, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'product_modifier_group_id', 'option_code', 'name',
        'price_adjustment', 'is_default', 'sort_order', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:4',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ProductModifierGroup::class, 'product_modifier_group_id');
    }
}
