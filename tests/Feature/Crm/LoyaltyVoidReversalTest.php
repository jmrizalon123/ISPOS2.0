<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class LoyaltyVoidReversalTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_voiding_sale_reverses_earned_loyalty_points(): void
    {
        $fixtures = $this->createPosFixtures();
        extract($fixtures);

        $product = $this->createRetailProduct($company, $tax, 100);

        $program = LoyaltyProgram::factory()->create([
            'company_id' => $company->id,
            'program_code' => 'PTS',
            'earn_rate' => 1,
            'is_default' => true,
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'customer_code' => 'LOYAL2',
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

        $sale = Sale::query()->where('store_id', $store->id)->firstOrFail();
        $customer->refresh();
        $pointsAfterSale = (float) $customer->loyalty_points;
        $this->assertTrue($pointsAfterSale > 0);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Store Manager');
        $manager->stores()->sync([$store->id]);

        $this->actingAs($manager)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame(0.0, (float) $customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'void_reversal',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);

        $this->assertSame(2, LoyaltyTransaction::query()->where('customer_id', $customer->id)->count());
    }
}
