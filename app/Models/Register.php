<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\RegisterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Register extends Model
{
    /** @use HasFactory<RegisterFactory> */
    use HasAuditUsers, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'store_id',
        'register_code',
        'register_name',
        'min',
        'permit_number',
        'device_serial',
        'reset_registration',
        'terminal_code',
        'terminal_name',
        'device_id',
        'device_name',
        'device_type',
        'ip_address',
        'mac_address',
        'printer_id',
        'cash_drawer_id',
        'customer_display_id',
        'kds_station_id',
        'receipt_printer_name',
        'receipt_printer_type',
        'receipt_printer_ip',
        'receipt_printer_port',
        'drawer_open_method',
        'allow_cash_sales',
        'allow_card_sales',
        'allow_gcash_sales',
        'allow_maya_sales',
        'allow_other_payments',
        'allow_discount',
        'allow_void',
        'allow_refund',
        'allow_reprint',
        'allow_price_override',
        'allow_open_drawer',
        'require_cashier_login',
        'require_manager_approval',
        'auto_print_receipt',
        'auto_print_kitchen_order',
        'auto_print_customer_receipt',
        'enable_customer_display',
        'enable_kds',
        'enable_ncs',
        'online_order_enabled',
        'offline_mode_enabled',
        'sync_enabled',
        'last_sync_at',
        'last_z_read_at',
        'current_shift_id',
        'current_cashier_id',
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
            'allow_cash_sales' => 'boolean',
            'allow_card_sales' => 'boolean',
            'allow_gcash_sales' => 'boolean',
            'allow_maya_sales' => 'boolean',
            'allow_other_payments' => 'boolean',
            'allow_discount' => 'boolean',
            'allow_void' => 'boolean',
            'allow_refund' => 'boolean',
            'allow_reprint' => 'boolean',
            'allow_price_override' => 'boolean',
            'allow_open_drawer' => 'boolean',
            'require_cashier_login' => 'boolean',
            'require_manager_approval' => 'boolean',
            'auto_print_receipt' => 'boolean',
            'auto_print_kitchen_order' => 'boolean',
            'auto_print_customer_receipt' => 'boolean',
            'enable_customer_display' => 'boolean',
            'enable_kds' => 'boolean',
            'enable_ncs' => 'boolean',
            'online_order_enabled' => 'boolean',
            'offline_mode_enabled' => 'boolean',
            'sync_enabled' => 'boolean',
            'is_active' => 'boolean',
            'reset_registration' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_z_read_at' => 'datetime',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Register $register): void {
            if (! $register->uuid) {
                $register->uuid = (string) Str::uuid();
            }

            if (! $register->company_id && $register->store_id) {
                $store = Store::query()->whereKey($register->store_id)->first(['company_id', 'currency']);
                $register->company_id = $store?->company_id;
                $register->currency ??= $store?->currency ?? 'PHP';
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'device_id');
    }

    public function defaultPriceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'default_price_group_id');
    }

    public function currentShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'current_shift_id');
    }

    public function currentCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_cashier_id');
    }
}
