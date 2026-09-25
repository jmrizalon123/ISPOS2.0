<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_code' => strtoupper(fake()->unique()->bothify('CAT##')),
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => 'active',
        ];
    }
}
