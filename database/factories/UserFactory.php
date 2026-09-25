<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'company_id' => Company::factory(),
            'employee_id' => fake()->optional()->bothify('EMP-####'),
            'username' => fake()->boolean(25) ? fake()->unique()->userName() : null,
            'default_store_id' => null,
            'default_warehouse_id' => null,
            'base_type' => 'store',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => null,
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'is_active' => true,
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'language' => 'en',
            'timezone' => 'Asia/Manila',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function headOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'base_type' => 'head_office',
            'default_store_id' => null,
            'default_warehouse_id' => null,
        ]);
    }
}
