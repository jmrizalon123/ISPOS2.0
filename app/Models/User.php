<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasAuditUsers, HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'employee_id',
        'username',
        'email',
        'phone',
        'password',
        'password_changed_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'display_name',
        'avatar',
        'department_id',
        'position_id',
        'default_store_id',
        'default_warehouse_id',
        'base_type',
        'name',
        'status',
        'is_active',
        'is_locked',
        'failed_login_attempts',
        'locked_at',
        'last_login_at',
        'last_login_ip',
        'last_activity_at',
        'language',
        'timezone',
        'preferences',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = ['avatar_url'];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! $user->uuid) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = trim((string) $this->avatar);

        if ($avatar === '') {
            return null;
        }

        if (Str::startsWith($avatar, ['http://', 'https://', '/'])) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    public function hasPendingTwoFactorAuthentication(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at === null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function defaultStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'default_store_id');
    }

    /** @deprecated Use defaultStore() */
    public function preferredStore(): BelongsTo
    {
        return $this->defaultStore();
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'default_warehouse_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class);
    }

    public function isHeadOfficeBased(): bool
    {
        return $this->base_type === 'head_office';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('Developer');
    }

    public function hasGlobalOrganizationAccess(): bool
    {
        return $this->isSuperAdmin() || $this->isDeveloper();
    }

    public function canAccessStore(Store|string $store): bool
    {
        if ($this->hasGlobalOrganizationAccess()) {
            return true;
        }

        if ($this->hasRole('Company Admin') || $this->isHeadOfficeBased()) {
            $storeModel = $store instanceof Store ? $store : Store::find($store);

            return $storeModel && $this->company_id === $storeModel->company_id;
        }

        $storeId = $store instanceof Store ? $store->id : $store;

        return $this->stores()->where('stores.id', $storeId)->exists();
    }

    public function canAccessCompany(Company|string $company): bool
    {
        if ($this->hasGlobalOrganizationAccess()) {
            return true;
        }

        $companyId = $company instanceof Company ? $company->id : $company;

        return $this->company_id === $companyId;
    }
}
