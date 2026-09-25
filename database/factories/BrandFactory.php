<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Brand> */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'brand_code' => strtoupper(fake()->unique()->bothify('BR##')),
            'name' => fake()->company(),
            'status' => 'active',
        ];
    }
}
