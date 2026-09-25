<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoyaltyProgram> */
class LoyaltyProgramFactory extends Factory
{
    protected $model = LoyaltyProgram::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'program_code' => strtoupper(fake()->unique()->bothify('LP##')),
            'name' => fake()->words(2, true).' Rewards',
            'earn_rate' => 1,
            'status' => 'active',
            'is_default' => false,
        ];
    }
}
