<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_retail_product_checkout_persists_sale_and_payment(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);
        $this->assertSame('completed', $sale->status);
        $this->assertSame($store->id, $sale->store_id);
        $this->assertSame('112.0000', (string) $sale->grand_total);

        $this->assertSame(1, SaleLine::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, SalePayment::query()->where('sale_id', $sale->id)->where('payment_method', 'cash')->count());
    }
}
