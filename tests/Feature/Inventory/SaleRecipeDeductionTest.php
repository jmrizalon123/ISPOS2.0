<?php

namespace Tests\Feature\Inventory;

use App\Models\ProductIngredient;
use App\Models\StockMovement;
use App\Models\StoreProductInventory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SaleRecipeDeductionTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_classic_burger_sale_deducts_required_ingredients(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        ['burger' => $burger, 'doubleOption' => $doubleOption] = $this->createBurgerWithModifiers($company, $tax);

        $bun = ProductIngredient::query()->where('product_id', $burger->id)->first()?->ingredientProduct;
        $patty = ProductIngredient::query()->where('product_id', $burger->id)->skip(1)->first()?->ingredientProduct;

        foreach ([$bun, $patty] as $ingredient) {
            StoreProductInventory::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $ingredient->id,
                'qty' => 50,
            ]);
        }

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

        $this->assertSame('49.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $bun->id)
            ->value('qty'));

        $this->assertGreaterThanOrEqual(2, StockMovement::query()->where('movement_type', 'recipe_consumption')->count());
    }
}
