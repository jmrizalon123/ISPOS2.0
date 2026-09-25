<?php

namespace Tests\Feature\Sales\Concerns;

use App\Models\Company;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\ProductComponent;
use App\Models\Register;
use App\Models\Store;
use App\Models\Tax;
use App\Models\User;

trait CreatesPosFixtures
{
    /** @return array{company: Company, store: Store, register: Register, tax: Tax, cashier: User} */
    protected function createPosFixtures(): array
    {
        $company = Company::factory()->create(['company_code' => 'POSCO']);
        $tax = Tax::factory()->create(['company_id' => $company->id, 'rate' => 12, 'is_inclusive' => false]);
        $priceGroup = PriceGroup::factory()->create(['company_id' => $company->id]);
        $store = Store::factory()->create([
            'company_id' => $company->id,
            'store_code' => 'MAIN',
            'price_group_id' => $priceGroup->id,
        ]);
        $register = Register::factory()->create(['store_id' => $store->id, 'register_code' => 'REG1']);

        $cashier = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cashier->assignRole('Cashier');
        $cashier->stores()->sync([$store->id]);

        return compact('company', 'store', 'register', 'tax', 'cashier');
    }

    protected function startPosSession(User $user, Store $store, Register $register, float $openingFloat = 0): void
    {
        $this->actingAs($user)
            ->post(route('pos.session.store'), [
                'store_id' => $store->id,
                'register_id' => $register->id,
            ])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($user)
            ->post(route('pos.shift.open'), ['opening_float' => $openingFloat])
            ->assertRedirect(route('pos.index'));
    }

    protected function createRetailProduct(Company $company, Tax $tax, float $price = 100): Product
    {
        return Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'product_type' => 'retail',
            'base_price' => $price,
            'status' => 'active',
        ]);
    }

    /** @return array{burger: Product, sizeGroup: ProductModifierGroup, doubleOption: ProductModifierOption} */
    protected function createBurgerWithModifiers(Company $company, Tax $tax): array
    {
        $burger = Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'sku' => 'BURGER-CLASSIC',
            'name' => 'Classic Burger',
            'product_type' => 'menu_item',
            'base_price' => 189,
            'has_modifiers' => true,
            'track_inventory' => false,
            'status' => 'active',
        ]);

        $this->seedBurgerIngredients($company, $tax, $burger);

        $sizeGroup = ProductModifierGroup::create([
            'product_id' => $burger->id,
            'group_code' => 'SIZE',
            'name' => 'Size',
            'selection_type' => 'single',
            'is_required' => true,
            'min_selections' => 1,
            'max_selections' => 1,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        ProductModifierOption::create([
            'product_modifier_group_id' => $sizeGroup->id,
            'option_code' => 'REG',
            'name' => 'Regular',
            'price_adjustment' => 0,
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $doubleOption = ProductModifierOption::create([
            'product_modifier_group_id' => $sizeGroup->id,
            'option_code' => 'DBL',
            'name' => 'Double Patty',
            'price_adjustment' => 60,
            'is_default' => false,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return compact('burger', 'sizeGroup', 'doubleOption');
    }

    /** @return array{combo: Product, burger: Product, drink: Product} */
    protected function createComboMeal(Company $company, Tax $tax): array
    {
        $burger = Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'product_type' => 'menu_item',
            'name' => 'Classic Burger',
            'track_inventory' => false,
            'status' => 'active',
        ]);

        $this->seedBurgerIngredients($company, $tax, $burger);

        $drink = Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'product_type' => 'retail',
            'name' => 'Cola',
            'track_inventory' => true,
            'status' => 'active',
        ]);

        $combo = Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'sku' => 'COMBO-BURGER',
            'name' => 'Burger Combo Meal',
            'product_type' => 'menu_item',
            'base_price' => 219,
            'has_components' => true,
            'status' => 'active',
        ]);

        ProductComponent::create([
            'product_id' => $combo->id,
            'component_product_id' => $burger->id,
            'quantity' => 1,
            'is_optional' => false,
            'sort_order' => 0,
        ]);

        ProductComponent::create([
            'product_id' => $combo->id,
            'component_product_id' => $drink->id,
            'quantity' => 1,
            'is_optional' => false,
            'sort_order' => 1,
        ]);

        return compact('combo', 'burger', 'drink');
    }

    protected function seedBurgerIngredients(Company $company, Tax $tax, Product $burger): void
    {
        foreach ([
            ['sku' => 'ING-BUN', 'name' => 'Burger Bun'],
            ['sku' => 'ING-PATTY', 'name' => 'Beef Patty'],
        ] as $index => $row) {
            $ingredient = Product::factory()->create([
                'company_id' => $company->id,
                'tax_id' => $tax->id,
                'sku' => $row['sku'],
                'name' => $row['name'],
                'product_type' => 'ingredient',
                'track_inventory' => true,
                'status' => 'active',
            ]);

            ProductIngredient::create([
                'product_id' => $burger->id,
                'ingredient_product_id' => $ingredient->id,
                'quantity' => 1,
                'is_optional' => false,
                'sort_order' => $index,
            ]);
        }
    }
}
