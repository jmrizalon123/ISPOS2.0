<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\LoyaltyProgramFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyProgram extends Model
{
    /** @use HasFactory<LoyaltyProgramFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id', 'program_code', 'name', 'earn_rate', 'redeem_value_per_point',
        'min_redeem_points', 'is_default', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'earn_rate' => 'decimal:4',
            'redeem_value_per_point' => 'decimal:4',
            'min_redeem_points' => 'decimal:4',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
