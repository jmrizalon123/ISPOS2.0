<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRefund extends Model
{
    /** @use HasFactory<\Database\Factories\SaleRefundFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'sale_id', 'company_id', 'store_id', 'pos_shift_id', 'user_id',
        'amount', 'payment_method', 'reason', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'refunded_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
