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

class StockTransfer extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransferFactory> */
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    public const TYPES = [
        'store_to_store',
        'warehouse_to_store',
        'store_to_warehouse',
        'warehouse_to_warehouse',
    ];

    public const STATUSES = [
        'draft',
        'pending_approval',
        'approved',
        'rejected',
        'processing',
        'in_transit',
        'partially_received',
        'received',
        'cancelled',
    ];

    public const PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    protected $fillable = [
        'uuid', 'company_id', 'transfer_no', 'transfer_number',
        'from_store_id', 'to_store_id', 'from_warehouse_id', 'to_warehouse_id',
        'transfer_date', 'requested_date', 'expected_date',
        'transfer_type', 'priority', 'reason', 'reference_no', 'notes', 'status',
        'requested_by', 'approved_by', 'approved_at',
        'released_by', 'released_at', 'received_by', 'received_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
        'total_items', 'total_quantity',
        'transferred_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'requested_date' => 'date',
            'expected_date' => 'date',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'transferred_at' => 'datetime',
            'total_items' => 'integer',
            'total_quantity' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StockTransfer $transfer): void {
            if ($transfer->transfer_no && ! $transfer->transfer_number) {
                $transfer->transfer_number = $transfer->transfer_no;
            }
            if ($transfer->transfer_number && ! $transfer->transfer_no) {
                $transfer->transfer_no = $transfer->transfer_number;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferLine::class)->orderBy('line_number');
    }

    public function lines(): HasMany
    {
        return $this->items();
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['received', 'completed'], true);
    }
}
