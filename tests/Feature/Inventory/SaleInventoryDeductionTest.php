<?php

namespace Tests\Feature\Inventory;

use App\Models\StockMovement;
use App\Models\StoreProductInventory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SaleInventoryDeductionTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_retail_pos_checkout_deducts_store_balance(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 20,
        ]);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $this->assertSame('18.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->assertSame(1, StockMovement::query()->where('movement_type', 'sale')->count());
    }
}
