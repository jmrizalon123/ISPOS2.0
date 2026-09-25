<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = fake()->city().' Store';

        return [
            'company_id' => Company::factory(),
            'store_code' => strtoupper(fake()->unique()->bothify('ST##')),
            'store_name' => $name,
            'legal_name' => $name,
            'store_type' => 'branch',
            'store_category' => 'retail',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'country' => 'PH',
            'currency' => 'PHP',
            'timezone' => 'Asia/Manila',
            'enable_pos' => true,
            'enable_inventory' => true,
            'operating_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
