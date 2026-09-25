<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    /** @use HasFactory<TaxFactory> */
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'tax_code', 'name', 'rate', 'is_inclusive', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_inclusive' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
