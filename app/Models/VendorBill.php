<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\VendorBillFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorBill extends Model
{
    /** @use HasFactory<VendorBillFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id', 'supplier_id', 'purchase_receipt_id', 'purchase_order_id',
        'bill_number', 'bill_date', 'due_date', 'status',
        'amount_due', 'amount_paid', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'amount_due' => 'decimal:4',
            'amount_paid' => 'decimal:4',
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

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function balanceDue(): string
    {
        return bcsub((string) $this->amount_due, (string) $this->amount_paid, 4);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'partial'], true);
    }
}
