<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesCompanyFields
{
    /**
     * @return array<string, mixed>
     */
    protected function companyFieldRules(?string $companyId = null): array
    {
        return [
            'company_code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('companies', 'company_code')->ignore($companyId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'max:50', Rule::in(['sole_proprietorship', 'partnership', 'corporation', 'cooperative', 'other'])],
            'industry' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tin' => ['nullable', 'string', 'max:30'],
            'bir_registration_no' => ['nullable', 'string', 'max:50'],
            'sec_registration_no' => ['nullable', 'string', 'max:50'],
            'dti_registration_no' => ['nullable', 'string', 'max:50'],
            'business_permit_no' => ['nullable', 'string', 'max:50'],
            'vat_registered' => ['sometimes', 'boolean'],
            'taxpayer_type' => ['nullable', 'string', 'max:50', Rule::in(['vat', 'non_vat', 'percentage_tax', 'exempt'])],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'string', 'max:255'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'receipt_header' => ['nullable', 'string', 'max:2000'],
            'receipt_footer' => ['nullable', 'string', 'max:2000'],
            'base_currency' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'fiscal_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'fiscal_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'accounting_method' => ['nullable', 'string', 'max:30', Rule::in(['accrual', 'cash'])],
            'default_payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'timezone' => ['required', 'string', 'max:64'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'string', 'max:20'],
            'language' => ['nullable', 'string', 'max:10'],
            'enable_pos' => ['sometimes', 'boolean'],
            'enable_inventory' => ['sometimes', 'boolean'],
            'enable_accounting' => ['sometimes', 'boolean'],
            'enable_hr' => ['sometimes', 'boolean'],
            'enable_crm' => ['sometimes', 'boolean'],
            'enable_ecommerce' => ['sometimes', 'boolean'],
            'subscription_plan_id' => ['nullable', 'string', 'max:26'],
            'subscription_start_at' => ['nullable', 'date'],
            'subscription_end_at' => ['nullable', 'date', 'after_or_equal:subscription_start_at'],
            'trial_ends_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'trial'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
