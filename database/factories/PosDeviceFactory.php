<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PosDevice;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PosDevice> */
class PosDeviceFactory extends Factory
{
    protected $model = PosDevice::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $register = Register::factory()->create(['store_id' => $store->id]);

        return [
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'registered_by' => User::factory()->create(['company_id' => $company->id])->id,
            'name' => fake()->words(2, true).' Terminal',
            'fingerprint' => fake()->uuid(),
            'status' => 'active',
        ];
    }
}
