<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorBill> */
class VendorBillFactory extends Factory
{
    protected $model = VendorBill::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'bill_number' => 'BILL-'.now()->format('Ymd').'-0001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'open',
            'amount_due' => 100,
            'amount_paid' => 0,
        ];
    }
}
