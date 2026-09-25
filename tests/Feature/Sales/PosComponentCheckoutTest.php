<?php

namespace Tests\Feature\Sales;

use App\Models\SaleLine;
use App\Models\SaleLineComponent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosComponentCheckoutTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_burger_combo_meal_component_snapshots_on_sale_line(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        ['combo' => $combo, 'burger' => $burger, 'drink' => $drink] = $this->createComboMeal($company, $tax);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $combo->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $line = SaleLine::query()->first();
        $this->assertNotNull($line);

        $components = SaleLineComponent::query()->where('sale_line_id', $line->id)->get();
        $this->assertCount(2, $components);
        $this->assertTrue($components->contains(fn ($c) => $c->component_product_id === $burger->id && $c->included));
        $this->assertTrue($components->contains(fn ($c) => $c->component_product_id === $drink->id && $c->included));
    }
}
