<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'brand_code', 'name', 'status', 'created_by', 'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
