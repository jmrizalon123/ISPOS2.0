<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'company_code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => $name,
            'legal_name' => $name.' Inc.',
            'display_name' => $name,
            'trade_name' => $name,
            'company_type' => 'corporation',
            'industry' => 'Retail',
            'vat_registered' => true,
            'taxpayer_type' => 'vat',
            'default_tax_rate' => 12,
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => 'PH',
            'base_currency' => 'PHP',
            'currency_symbol' => '₱',
            'timezone' => 'Asia/Manila',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'language' => 'en',
            'enable_pos' => true,
            'enable_inventory' => true,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
