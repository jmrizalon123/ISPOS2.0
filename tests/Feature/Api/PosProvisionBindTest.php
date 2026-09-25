<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Register;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProvisionBindTest extends TestCase
{
    use RefreshDatabase;

    public function test_binds_register_to_device_serial(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $register = Register::factory()->create([
            'store_id' => $store->id,
            'register_code' => 'REG1',
            'reset_registration' => true,
        ]);

        $this->postJson('/api/v1/pos/provision/bind', [
            'register_code' => 'REG1',
            'device_serial' => 'DISK-ABC123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $register->refresh();
        $this->assertSame('DISK-ABC123', $register->device_serial);
        $this->assertFalse($register->reset_registration);
    }

    public function test_bind_rejects_other_device_when_not_reset(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        Register::factory()->create([
            'store_id' => $store->id,
            'register_code' => 'REG1',
            'device_serial' => 'DISK-OTHER',
            'reset_registration' => false,
        ]);

        $this->postJson('/api/v1/pos/provision/bind', [
            'register_code' => 'REG1',
            'device_serial' => 'DISK-ABC123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('register_code');
    }
}
