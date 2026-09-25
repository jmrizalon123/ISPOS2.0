<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplierPayment> */
class SupplierPaymentFactory extends Factory
{
    protected $model = SupplierPayment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'payment_number' => 'PAY-'.now()->format('Ymd').'-0001',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount' => 100,
        ];
    }
}
