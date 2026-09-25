<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Promotion;
use App\Models\Sale;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PromotionCheckoutTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_promotion_reduces_checkout_total_and_earns_loyalty_points(): void
    {
        $fixtures = $this->createPosFixtures();
        extract($fixtures);

        $product = $this->createRetailProduct($company, $tax, 100);

        Promotion::factory()->create([
            'company_id' => $company->id,
            'promo_code' => 'OFF10',
            'name' => '10% Off',
            'promo_type' => 'percent_off',
            'discount_value' => 10,
            'applies_to' => 'all',
            'status' => 'active',
            'starts_at' => now()->subDay(),
        ]);

        $program = LoyaltyProgram::factory()->create([
            'company_id' => $company->id,
            'program_code' => 'PTS',
            'earn_rate' => 1,
            'is_default' => true,
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'customer_code' => 'LOYAL1',
            'loyalty_program_id' => $program->id,
            'loyalty_points' => 0,
        ]);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.customer.store'), ['customer_id' => $customer->id])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->where('store_id', $store->id)->first();
        $this->assertNotNull($sale);
        $this->assertSame($customer->id, $sale->customer_id);
        $this->assertNotNull($sale->promotion_id);
        $this->assertTrue((float) $sale->discount_total > 0);

        $customer->refresh();
        $this->assertTrue((float) $customer->loyalty_points > 0);
        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'earn',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }
}
