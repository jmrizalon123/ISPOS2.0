<?php

namespace Database\Factories;

use App\Models\Register;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Register>
 */
class RegisterFactory extends Factory
{
    protected $model = Register::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'register_code' => strtoupper(fake()->unique()->bothify('REG#')),
            'register_name' => 'Register '.fake()->numberBetween(1, 9),
            'status' => 'active',
        ];
    }
}
