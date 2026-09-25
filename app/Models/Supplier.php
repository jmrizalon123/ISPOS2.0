<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id', 'supplier_code', 'name', 'contact_name', 'email', 'phone',
        'address', 'payment_terms', 'notes', 'status', 'created_by', 'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
