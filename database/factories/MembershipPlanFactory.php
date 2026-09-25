<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipPlan> */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_code' => strtoupper(fake()->unique()->bothify('MBR##')),
            'name' => fake()->words(2, true).' Member',
            'discount_percent' => 5,
            'duration_days' => 365,
            'status' => 'active',
        ];
    }
}
