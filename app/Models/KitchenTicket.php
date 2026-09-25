<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicket extends Model
{
    /** @use HasFactory<\Database\Factories\KitchenTicketFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'company_id', 'store_id', 'sale_id', 'ticket_number', 'status',
        'item_count', 'queued_at', 'started_at', 'ready_at', 'completed_at',
        'cancelled_at', 'started_by', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'preparing', 'ready'], true);
    }
}
