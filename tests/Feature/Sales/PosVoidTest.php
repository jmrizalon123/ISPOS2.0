<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosVoidTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_void_requires_pos_void_permission(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 50);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 100])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->firstOrFail();

        $this->actingAs($cashier)
            ->post(route('pos.sales.void', $sale))
            ->assertForbidden();

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

        $this->assertSame('voided', $sale->fresh()->status);
    }
}
