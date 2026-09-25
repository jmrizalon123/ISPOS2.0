<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'supplier_id' => Supplier::factory(),
            'po_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'subtotal' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
        ];
    }
}
