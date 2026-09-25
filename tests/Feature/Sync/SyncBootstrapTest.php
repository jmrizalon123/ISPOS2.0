<?php

namespace Tests\Feature\Sync;

use App\Domains\Sync\Services\PosDeviceService;
use App\Models\Product;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SyncBootstrapTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bootstrap_returns_catalog_snapshot_for_registered_device(): void
    {
        ['company' => $company, 'store' => $store, 'register' => $register, 'tax' => $tax, 'cashier' => $cashier] = $this->createPosFixtures();

        Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'sku' => 'SYNC-SKU-1',
            'name' => 'Sync Test Product',
            'product_type' => 'retail',
            'status' => 'active',
        ]);

        $registration = app(PosDeviceService::class)->register($cashier, $register, 'Sync Terminal', 'fp-sync-1');

        $response = $this->withToken($registration['plain_text_token'])
            ->getJson('/api/v1/sync/bootstrap')
            ->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.store.id', $store->id)
            ->assertJsonPath('data.register.id', $register->id);

        $products = $response->json('data.products');
        $this->assertNotEmpty($products);
        $this->assertSame('Sync Test Product', $products[0]['name']);
    }

    public function test_bootstrap_honors_device_bearer_token_when_session_is_also_present(): void
    {
        ['register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();

        $registration = app(PosDeviceService::class)->register($cashier, $register, 'Sync Terminal', 'fp-sync-2');

        Sanctum::actingAs($cashier);

        $this->withHeader('Authorization', 'Bearer '.$registration['plain_text_token'])
            ->getJson('/api/v1/sync/bootstrap')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
