<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU####')),
            'name' => fake()->words(3, true),
            'product_type' => 'retail',
            'cost' => fake()->randomFloat(4, 10, 500),
            'base_price' => fake()->randomFloat(4, 20, 800),
            'track_inventory' => true,
            'qty' => fake()->randomFloat(4, 0, 200),
            'ideal_qty' => fake()->randomFloat(4, 20, 100),
            'warning_qty' => fake()->randomFloat(4, 5, 20),
            'has_variants' => false,
            'has_modifiers' => false,
            'has_components' => false,
            'status' => 'active',
        ];
    }
}
