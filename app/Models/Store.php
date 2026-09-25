<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $appends = ['logo_url'];

    protected $fillable = [
        'uuid',
        'company_id',
        'store_code',
        'store_name',
        'legal_name',
        'store_type',
        'store_category',
        'description',
        'branch_code',
        'tin',
        'bir_registration_no',
        'business_permit_no',
        'email',
        'phone',
        'mobile',
        'address_line_1',
        'address_line_2',
        'barangay',
        'city',
        'province',
        'region',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'manager_id',
        'warehouse_id',
        'price_group_id',
        'sales_plan_id',
        'default_tax_rate',
        'currency',
        'timezone',
        'opening_time',
        'closing_time',
        'operating_days',
        'is_24_hours',
        'enable_pos',
        'enable_inventory',
        'enable_online_ordering',
        'enable_delivery',
        'enable_pickup',
        'enable_dine_in',
        'enable_takeaway',
        'receipt_header',
        'receipt_footer',
        'logo',
        'status',
        'is_active',
        'opened_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'default_tax_rate' => 'decimal:2',
            'operating_days' => 'array',
            'is_24_hours' => 'boolean',
            'enable_pos' => 'boolean',
            'enable_inventory' => 'boolean',
            'enable_online_ordering' => 'boolean',
            'enable_delivery' => 'boolean',
            'enable_pickup' => 'boolean',
            'enable_dine_in' => 'boolean',
            'enable_takeaway' => 'boolean',
            'is_active' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Store $store): void {
            if (! $store->uuid) {
                $store->uuid = (string) Str::uuid();
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        $logo = trim((string) $this->logo);

        if ($logo === '') {
            return null;
        }

        if (Str::startsWith($logo, ['http://', 'https://', '/'])) {
            return $logo;
        }

        return Storage::disk('public')->url($logo);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class);
    }

    public function salesPlan(): BelongsTo
    {
        return $this->belongsTo(SalesPlan::class);
    }

    public function registers(): HasMany
    {
        return $this->hasMany(Register::class);
    }

    public function onlineStoreSetting(): HasOne
    {
        return $this->hasOne(OnlineStoreSetting::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** @deprecated Use PosUiResolver::resolve() for settings-aware layout. */
    public function posUiMode(): string
    {
        return app(\App\Support\PosUiResolver::class)->resolve($this);
    }

    public function posUiLayout(): string
    {
        return $this->posUiMode();
    }
}
