<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalesPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesPlan> */
class SalesPlanFactory extends Factory
{
    protected $model = SalesPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_code' => strtoupper(fake()->unique()->bothify('PLAN##')),
            'name' => fake()->randomElement(['Retail', 'Restaurant', 'Wholesale']),
            'description' => 'Stores that share the same product catalog.',
            'status' => 'active',
        ];
    }
}
