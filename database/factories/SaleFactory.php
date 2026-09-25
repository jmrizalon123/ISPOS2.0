<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PosShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'sale_number' => strtoupper(fake()->unique()->bothify('MAIN-########-####')),
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'register_id' => Register::factory(),
            'pos_shift_id' => null,
            'user_id' => User::factory(),
            'status' => 'completed',
            'subtotal' => 100,
            'tax_total' => 12,
            'discount_total' => 0,
            'grand_total' => 112,
            'completed_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Sale $sale) {
            if ($sale->store_id) {
                $store = Store::query()->find($sale->store_id);
                if ($store) {
                    $sale->company_id = $sale->company_id ?? $store->company_id;
                }
            }

            if ($sale->store_id && $sale->register_id && $sale->user_id && ! $sale->pos_shift_id) {
                $sale->pos_shift_id = PosShift::factory()->create([
                    'company_id' => $sale->company_id,
                    'store_id' => $sale->store_id,
                    'register_id' => $sale->register_id,
                    'user_id' => $sale->user_id,
                ])->id;
            }
        });
    }
}
