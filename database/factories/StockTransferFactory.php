<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\StockTransfer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<StockTransfer> */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        $number = 'TRF-'.fake()->unique()->numerify('########');

        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'from_store_id' => Store::factory(),
            'to_store_id' => Store::factory(),
            'transfer_no' => $number,
            'transfer_number' => $number,
            'transfer_type' => 'store_to_store',
            'priority' => 'normal',
            'status' => 'received',
            'notes' => null,
            'transfer_date' => now()->toDateString(),
            'requested_date' => now()->toDateString(),
            'transferred_at' => now(),
            'received_at' => now(),
            'total_items' => 0,
            'total_quantity' => 0,
        ];
    }
}
