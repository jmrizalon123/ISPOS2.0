<?php

namespace Tests\Feature\Inventory;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SaleVoidReversalTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_void_restores_store_inventory(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 50);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('9.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $manager = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $manager->assignRole('Store Manager');
        $manager->stores()->sync([$store->id]);

        $this->actingAs($manager)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $this->assertSame('10.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->assertSame(1, StockMovement::query()->where('movement_type', 'void')->count());
    }
}
