<?php

namespace Tests\Feature\Sync;

use App\Models\Register;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosDeviceRegisterTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_can_register_pos_device_and_receive_token(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();

        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/v1/devices/register', [
            'register_id' => $register->id,
            'name' => 'Front Counter Terminal',
            'fingerprint' => 'device-fp-001',
        ])->assertCreated();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.device.register_id', $register->id)
            ->assertJsonPath('data.device.store_id', $store->id);

        $this->assertDatabaseHas('pos_devices', [
            'register_id' => $register->id,
            'name' => 'Front Counter Terminal',
            'status' => 'active',
        ]);
    }

    public function test_sync_bootstrap_requires_device_token(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/sync/bootstrap')->assertForbidden();
    }
}
