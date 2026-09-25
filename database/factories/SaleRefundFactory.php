<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleRefund> */
class SaleRefundFactory extends Factory
{
    protected $model = SaleRefund::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'company_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->company_id,
            'store_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->store_id,
            'pos_shift_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->pos_shift_id,
            'user_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->user_id,
            'amount' => 100,
            'payment_method' => 'cash',
            'reason' => 'Customer return',
            'refunded_at' => now(),
        ];
    }
}
