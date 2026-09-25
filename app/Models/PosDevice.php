<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosDevice extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'store_id',
        'register_id',
        'registered_by',
        'name',
        'fingerprint',
        'status',
        'personal_access_token_id',
        'last_bootstrap_at',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'last_bootstrap_at' => 'datetime',
            'last_sync_at' => 'datetime',
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

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
