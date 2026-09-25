<?php

namespace Tests\Feature\Kds;

use App\Models\KitchenTicket;
use App\Models\Sale;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class KitchenTicketOnCheckoutTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_menu_item_checkout_creates_kitchen_ticket(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        ['burger' => $burger, 'doubleOption' => $doubleOption] = $this->createBurgerWithModifiers($company, $tax);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), [
                'product_id' => $burger->id,
                'modifier_option_ids' => [$doubleOption->id],
            ])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);

        $this->assertDatabaseHas('kitchen_tickets', [
            'sale_id' => $sale->id,
            'store_id' => $store->id,
            'status' => 'pending',
            'item_count' => 1,
        ]);

        $ticket = KitchenTicket::query()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame('K-'.$sale->sale_number, $ticket->ticket_number);
    }

    public function test_retail_only_checkout_does_not_create_kitchen_ticket(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseCount('kitchen_tickets', 0);
    }

    public function test_voiding_sale_cancels_kitchen_ticket(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        ['burger' => $burger, 'doubleOption' => $doubleOption] = $this->createBurgerWithModifiers($company, $tax);

        $cashier->givePermissionTo('pos.void');

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), [
                'product_id' => $burger->id,
                'modifier_option_ids' => [$doubleOption->id],
            ])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);

        $this->actingAs($cashier)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $this->assertDatabaseHas('kitchen_tickets', [
            'sale_id' => $sale->id,
            'status' => 'cancelled',
        ]);
    }
}
