<?php

namespace Tests\Feature\Inventory;

use App\Models\StockMovement;
use App\Models\StoreProductInventory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SaleComponentDeductionTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_burger_combo_deducts_cola_and_burger_ingredients(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        ['combo' => $combo, 'burger' => $burger, 'drink' => $drink] = $this->createComboMeal($company, $tax);

        $burger->load('ingredients.ingredientProduct');
        $ingredients = $burger->ingredients->where('is_optional', false);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $drink->id,
            'qty' => 30,
        ]);

        foreach ($ingredients as $ingredient) {
            StoreProductInventory::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $ingredient->ingredient_product_id,
                'qty' => 40,
            ]);
        }

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $combo->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 500])
            ->assertRedirect(route('pos.index'));

        $this->assertSame('29.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $drink->id)
            ->value('qty'));

        $this->assertTrue(StockMovement::query()->where('movement_type', 'component_consumption')->exists()
            || StockMovement::query()->where('movement_type', 'recipe_consumption')->exists());
    }
}
