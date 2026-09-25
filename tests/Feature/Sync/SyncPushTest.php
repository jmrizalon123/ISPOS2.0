<?php

namespace Tests\Feature\Sync;

use App\Domains\Sync\Services\PosDeviceService;
use App\Models\PosShift;
use App\Models\Sale;
use App\Models\StoreProductInventory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SyncPushTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_push_accepts_offline_sale_and_is_idempotent_by_client_uuid(): void
    {
        ['company' => $company, 'store' => $store, 'register' => $register, 'tax' => $tax, 'cashier' => $cashier] = $this->createPosFixtures();

        $product = $this->createRetailProduct($company, $tax, 150);

        StoreProductInventory::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 100,
        ]);

        $shift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        $registration = app(PosDeviceService::class)->register($cashier, $register, 'Push Terminal', 'fp-push-1');
        $token = $registration['plain_text_token'];

        $payload = [
            'sales' => [[
                'client_uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'pos_shift_id' => $shift->id,
                'completed_at' => now()->toIso8601String(),
                'subtotal' => '150.0000',
                'tax_total' => '18.0000',
                'discount_total' => '0.0000',
                'grand_total' => '168.0000',
                'lines' => [[
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => '1.0000',
                    'unit_price' => '150.0000',
                    'line_subtotal' => '150.0000',
                    'tax_amount' => '18.0000',
                    'line_total' => '168.0000',
                ]],
                'payment' => ['method' => 'cash', 'amount' => '168.0000'],
            ]],
        ];

        $this->withToken($token)
            ->postJson('/api/v1/sync/push', $payload)
            ->assertOk()
            ->assertJsonPath('data.accepted.0.client_uuid', '550e8400-e29b-41d4-a716-446655440000');

        $this->assertDatabaseHas('sales', [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'sync_source' => 'offline_sync',
            'store_id' => $store->id,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/sync/push', $payload)
            ->assertOk()
            ->assertJsonPath('data.duplicates.0.client_uuid', '550e8400-e29b-41d4-a716-446655440000');

        $this->assertSame(1, Sale::query()->where('uuid', '550e8400-e29b-41d4-a716-446655440000')->count());
    }
}
