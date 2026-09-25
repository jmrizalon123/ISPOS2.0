<?php

namespace Database\Factories;

use App\Models\KitchenTicket;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KitchenTicket> */
class KitchenTicketFactory extends Factory
{
    protected $model = KitchenTicket::class;

    public function definition(): array
    {
        return [
            'company_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->company_id,
            'store_id' => fn (array $attributes) => Sale::query()->find($attributes['sale_id'])?->store_id,
            'sale_id' => Sale::factory(),
            'ticket_number' => 'K-'.fake()->unique()->numerify('MAIN-20260910-####'),
            'status' => 'pending',
            'item_count' => 1,
            'queued_at' => now(),
        ];
    }
}
