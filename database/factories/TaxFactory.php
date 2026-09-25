<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tax> */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'tax_code' => 'VAT12',
            'name' => 'VAT 12%',
            'rate' => 12,
            'is_inclusive' => false,
            'status' => 'active',
        ];
    }
}
