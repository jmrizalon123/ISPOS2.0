<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'promo_code' => strtoupper(fake()->unique()->bothify('PROMO##')),
            'name' => fake()->words(3, true),
            'promo_type' => 'percent_off',
            'discount_value' => 10,
            'applies_to' => 'all',
            'status' => 'draft',
        ];
    }
}
