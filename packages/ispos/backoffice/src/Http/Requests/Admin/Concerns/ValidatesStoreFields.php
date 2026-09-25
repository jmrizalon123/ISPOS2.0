<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use App\Domains\Organization\Services\StoreLogoService;
use Illuminate\Validation\Rule;

trait ValidatesStoreFields
{
    /**
     * @return array<string, mixed>
     */
    protected function storeFieldRules(?string $storeId = null, ?string $companyId = null): array
    {
        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'store_code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('stores', 'store_code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($storeId),
            ],
            'store_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'store_type' => ['nullable', 'string', 'max:50', Rule::in(['head_office', 'branch', 'warehouse', 'outlet', 'kiosk', 'popup', 'other'])],
            'store_category' => ['nullable', 'string', 'max:50', Rule::in(['retail', 'wholesale', 'restaurant', 'cafe', 'grocery', 'pharmacy', 'other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'tin' => ['nullable', 'string', 'max:30'],
            'bir_registration_no' => ['nullable', 'string', 'max:50'],
            'business_permit_no' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'manager_id' => ['nullable', 'ulid', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'string', 'max:26'],
            'price_group_id' => ['nullable', 'string', 'max:26'],
            'sales_plan_id' => [
                'nullable',
                'ulid',
                Rule::exists('sales_plans', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'operating_days' => ['nullable', 'array'],
            'operating_days.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'is_24_hours' => ['sometimes', 'boolean'],
            'enable_pos' => ['sometimes', 'boolean'],
            'enable_inventory' => ['sometimes', 'boolean'],
            'enable_online_ordering' => ['sometimes', 'boolean'],
            'enable_delivery' => ['sometimes', 'boolean'],
            'enable_pickup' => ['sometimes', 'boolean'],
            'enable_dine_in' => ['sometimes', 'boolean'],
            'enable_takeaway' => ['sometimes', 'boolean'],
            'receipt_header' => ['nullable', 'string', 'max:2000'],
            'receipt_footer' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.StoreLogoService::MAX_KILOBYTES],
            'remove_logo' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'closed'])],
            'is_active' => ['sometimes', 'boolean'],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
        ];
    }

    protected function mergeStoreNullableFields(): void
    {
        foreach ([
            'manager_id',
            'warehouse_id',
            'price_group_id',
            'sales_plan_id',
            'default_tax_rate',
            'latitude',
            'longitude',
            'opened_at',
            'closed_at',
            'opening_time',
            'closing_time',
        ] as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => null]);
            }
        }
    }

    protected function mergeStoreBooleanFields(): void
    {
        foreach ([
            'is_24_hours',
            'enable_pos',
            'enable_inventory',
            'enable_online_ordering',
            'enable_delivery',
            'enable_pickup',
            'enable_dine_in',
            'enable_takeaway',
            'is_active',
            'remove_logo',
        ] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
