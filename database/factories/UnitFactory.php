<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unit> */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'unit_code' => strtoupper(fake()->unique()->bothify('U##')),
            'name' => 'Piece',
            'symbol' => 'pc',
            'status' => 'active',
        ];
    }
}
