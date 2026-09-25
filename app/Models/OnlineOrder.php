<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogUuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineOrder extends Model
{
    use HasCatalogUuid, HasUlids, SoftDeletes;

    public const STATUSES = [
        'pending',
        'accepted',
        'preparing',
        'ready',
        'completed',
        'cancelled',
        'rejected',
    ];

    public const FULFILLMENT_TYPES = ['pickup', 'delivery', 'dine_in'];

    protected $fillable = [
        'uuid',
        'order_number',
        'company_id',
        'store_id',
        'customer_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'fulfillment_type',
        'status',
        'payment_method',
        'payment_status',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_barangay',
        'delivery_city',
        'delivery_province',
        'delivery_postal_code',
        'delivery_notes',
        'customer_notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'delivery_fee',
        'grand_total',
        'currency',
        'sale_id',
        'accepted_at',
        'rejected_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
        'accepted_by',
        'rejected_by',
        'rejection_reason',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'delivery_fee' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OnlineOrderLine::class)->orderBy('line_number');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'accepted', 'preparing', 'ready'], true);
    }
}
