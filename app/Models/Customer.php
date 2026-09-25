<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use App\Models\Concerns\HasCatalogUuid;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasAuditUsers, HasCatalogUuid, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'customer_code', 'first_name', 'last_name', 'email', 'password', 'phone',
        'mobile', 'birth_date', 'address_line_1', 'city', 'province', 'postal_code',
        'price_group_id', 'loyalty_program_id', 'loyalty_points', 'notes', 'status',
        'created_by', 'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'loyalty_points' => 'decimal:4',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->orderByDesc('created_at');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class)->orderByDesc('started_at');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ProductFavorite::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ProductRating::class);
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function activeMembership(): ?CustomerMembership
    {
        return $this->memberships()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('membershipPlan')
            ->first();
    }
}
