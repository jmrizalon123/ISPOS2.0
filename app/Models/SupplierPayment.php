<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\SupplierPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    /** @use HasFactory<SupplierPaymentFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id', 'supplier_id', 'payment_number', 'payment_date',
        'payment_method', 'amount', 'reference', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }
}
