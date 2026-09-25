<?php

namespace Tests\Feature\Sales;

use App\Models\SaleLine;
use App\Models\SaleLineModifier;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosRestaurantCheckoutTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_classic_burger_with_modifier_options_priced_correctly(): void
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

        $line = SaleLine::query()->first();
        $this->assertNotNull($line);
        $this->assertSame('249.0000', (string) $line->unit_price);

        $modifier = SaleLineModifier::query()->where('sale_line_id', $line->id)->first();
        $this->assertNotNull($modifier);
        $this->assertSame('Double Patty', $modifier->option_name);
        $this->assertSame('60.0000', (string) $modifier->price_adjustment);
    }
}
