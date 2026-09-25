<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesRegisterFields
{
    /**
     * @return array<string, mixed>
     */
    protected function registerFieldRules(?string $registerId = null, ?string $storeId = null): array
    {
        return [
            'store_id' => ['required', 'ulid', 'exists:stores,id'],
            'register_code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('registers', 'register_code')
                    ->where(fn ($q) => $q->where('store_id', $storeId))
                    ->ignore($registerId),
            ],
            'register_name' => ['required', 'string', 'max:255'],
            'min' => ['nullable', 'string', 'max:100'],
            'permit_number' => ['nullable', 'string', 'max:100'],
            'device_serial' => ['nullable', 'string', 'max:100'],
            'reset_registration' => ['sometimes', 'boolean'],
            'terminal_code' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'terminal_name' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'ulid', 'exists:pos_devices,id'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:50', Rule::in(['pos_terminal', 'tablet', 'mobile', 'kiosk', 'other'])],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:17'],
            'printer_id' => ['nullable', 'string', 'max:26'],
            'cash_drawer_id' => ['nullable', 'string', 'max:26'],
            'customer_display_id' => ['nullable', 'string', 'max:26'],
            'kds_station_id' => ['nullable', 'string', 'max:26'],
            'receipt_printer_name' => ['nullable', 'string', 'max:255'],
            'receipt_printer_type' => ['nullable', 'string', 'max:50', Rule::in(['network', 'usb', 'bluetooth', 'serial', 'other'])],
            'receipt_printer_ip' => ['nullable', 'string', 'max:45'],
            'receipt_printer_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'drawer_open_method' => ['nullable', 'string', 'max:50', Rule::in(['pulse', 'command', 'manual', 'other'])],
            'allow_cash_sales' => ['sometimes', 'boolean'],
            'allow_card_sales' => ['sometimes', 'boolean'],
            'allow_gcash_sales' => ['sometimes', 'boolean'],
            'allow_maya_sales' => ['sometimes', 'boolean'],
            'allow_other_payments' => ['sometimes', 'boolean'],
            'allow_discount' => ['sometimes', 'boolean'],
            'allow_void' => ['sometimes', 'boolean'],
            'allow_refund' => ['sometimes', 'boolean'],
            'allow_reprint' => ['sometimes', 'boolean'],
            'allow_price_override' => ['sometimes', 'boolean'],
            'allow_open_drawer' => ['sometimes', 'boolean'],
            'require_cashier_login' => ['sometimes', 'boolean'],
            'require_manager_approval' => ['sometimes', 'boolean'],
            'auto_print_receipt' => ['sometimes', 'boolean'],
            'auto_print_kitchen_order' => ['sometimes', 'boolean'],
            'auto_print_customer_receipt' => ['sometimes', 'boolean'],
            'enable_customer_display' => ['sometimes', 'boolean'],
            'enable_kds' => ['sometimes', 'boolean'],
            'enable_ncs' => ['sometimes', 'boolean'],
            'online_order_enabled' => ['sometimes', 'boolean'],
            'offline_mode_enabled' => ['sometimes', 'boolean'],
            'sync_enabled' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function mergeRegisterNullableFields(): void
    {
        foreach ([
            'min',
            'permit_number',
            'device_serial',
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
        ] as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => null]);
            }
        }
    }

    protected function mergeRegisterBooleanFields(): void
    {
        foreach ([
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
            'is_active',
            'reset_registration',
        ] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
