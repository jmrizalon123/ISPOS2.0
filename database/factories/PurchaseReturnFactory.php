<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseReturn> */
class PurchaseReturnFactory extends Factory
{
    protected $model = PurchaseReturn::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'supplier_id' => Supplier::factory(),
            'return_number' => 'PR-'.fake()->unique()->numerify('######'),
            'status' => 'draft',
            'reason' => 'Damaged goods',
        ];
    }
}
