<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\PublicStorage;

class ProductImage extends Model
{
    use HasAuditUsers, HasUlids, SoftDeletes;

    protected $fillable = [
        'product_id', 'path', 'original_name', 'mime_type', 'size_bytes',
        'sort_order', 'is_default', 'created_by', 'updated_by',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): ?string
    {
        return PublicStorage::url($this->path);
    }
}
