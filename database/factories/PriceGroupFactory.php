<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PriceGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceGroup> */
class PriceGroupFactory extends Factory
{
    protected $model = PriceGroup::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'group_code' => 'RETAIL',
            'name' => 'Retail',
            'is_default' => true,
            'status' => 'active',
        ];
    }
}
