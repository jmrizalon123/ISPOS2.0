<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChartOfAccount> */
class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    public function definition(): array
    {
        $type = fake()->randomElement(ChartOfAccount::TYPES);

        return [
            'company_id' => Company::factory(),
            'account_code' => fake()->unique()->numerify('####'),
            'account_name' => fake()->words(2, true),
            'account_type' => $type,
            'normal_balance' => ChartOfAccount::normalBalanceForType($type),
            'is_system' => false,
            'status' => 'active',
        ];
    }
}
