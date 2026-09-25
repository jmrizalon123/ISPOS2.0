<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_code',
        'name',
        'legal_name',
        'display_name',
        'trade_name',
        'company_type',
        'industry',
        'description',
        'tin',
        'bir_registration_no',
        'sec_registration_no',
        'dti_registration_no',
        'business_permit_no',
        'vat_registered',
        'taxpayer_type',
        'default_tax_rate',
        'email',
        'phone',
        'mobile',
        'website',
        'address_line_1',
        'address_line_2',
        'barangay',
        'city',
        'province',
        'region',
        'country',
        'postal_code',
        'logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'receipt_header',
        'receipt_footer',
        'base_currency',
        'currency_symbol',
        'fiscal_year_start_month',
        'fiscal_year_start_day',
        'accounting_method',
        'default_payment_terms_days',
        'timezone',
        'date_format',
        'time_format',
        'language',
        'enable_pos',
        'enable_inventory',
        'enable_accounting',
        'enable_hr',
        'enable_crm',
        'enable_ecommerce',
        'subscription_plan_id',
        'subscription_start_at',
        'subscription_end_at',
        'trial_ends_at',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'vat_registered' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'fiscal_year_start_month' => 'integer',
            'fiscal_year_start_day' => 'integer',
            'default_payment_terms_days' => 'integer',
            'enable_pos' => 'boolean',
            'enable_inventory' => 'boolean',
            'enable_accounting' => 'boolean',
            'enable_hr' => 'boolean',
            'enable_crm' => 'boolean',
            'enable_ecommerce' => 'boolean',
            'is_active' => 'boolean',
            'subscription_start_at' => 'datetime',
            'subscription_end_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (! $company->uuid) {
                $company->uuid = (string) Str::uuid();
            }
        });
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function salesPlans(): HasMany
    {
        return $this->hasMany(SalesPlan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

}
