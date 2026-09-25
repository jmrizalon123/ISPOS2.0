<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'sale_number', 'company_id', 'store_id', 'register_id', 'pos_device_id',
        'pos_shift_id', 'user_id', 'customer_id', 'promotion_id', 'status', 'sync_source',
        'source', 'order_type', 'online_order_id',
        'subtotal', 'tax_total', 'discount_total', 'loyalty_points_earned',
        'grand_total', 'notes', 'completed_at', 'voided_at', 'voided_by',
        'refunded_at', 'refunded_by', 'refund_reason',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'loyalty_points_earned' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class)->orderBy('line_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function kitchenTicket(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(KitchenTicket::class);
    }

    public function refund(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SaleRefund::class);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function refundedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
