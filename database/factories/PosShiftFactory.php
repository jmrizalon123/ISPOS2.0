<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PosShift;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PosShift> */
class PosShiftFactory extends Factory
{
    protected $model = PosShift::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'register_id' => Register::factory(),
            'user_id' => User::factory(),
            'status' => 'closed',
            'opening_float' => 500,
            'closing_float' => 500,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ];
    }
}
