<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\StockMovement;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosRefundTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_refund_requires_pos_refund_permission(): void
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
            ->post(route('pos.sales.refund', $sale), ['reason' => 'Customer return'])
            ->assertForbidden();
    }

    public function test_store_manager_can_refund_completed_sale_and_restore_stock(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 50);

        StoreProductInventory::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 100])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('9.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->actingAs($cashier)
            ->post(route('pos.shift.close'), ['closing_float' => 1050])
            ->assertRedirect();

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Store Manager');
        $manager->stores()->sync([$store->id]);

        $this->startPosSession($manager, $store, $register);

        $this->actingAs($manager)
            ->post(route('pos.sales.refund', $sale), ['reason' => 'Customer return'])
            ->assertRedirect(route('pos.index'));

        $this->assertSame('refunded', $sale->fresh()->status);
        $this->assertDatabaseHas('sale_refunds', [
            'sale_id' => $sale->id,
            'reason' => 'Customer return',
        ]);
        $this->assertTrue(SaleRefund::query()->where('sale_id', $sale->id)->exists());
        $this->assertSame('10.0000', (string) StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->value('qty'));
        $this->assertTrue(
            StockMovement::query()
                ->where('sale_id', $sale->id)
                ->where('movement_type', 'refund')
                ->exists()
        );
    }

    public function test_cannot_refund_voided_sale(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 50);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Store Manager');
        $manager->stores()->sync([$store->id]);

        $this->startPosSession($manager, $store, $register);

        $this->actingAs($manager)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 100])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $this->actingAs($manager)
            ->from(route('pos.index'))
            ->post(route('pos.sales.refund', $sale), ['reason' => 'Should fail'])
            ->assertRedirect()
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('sale_refunds', 0);
    }

    public function test_refundable_lookup_lists_completed_sales(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 50);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Store Manager');
        $manager->stores()->sync([$store->id]);

        $this->startPosSession($manager, $store, $register);

        $this->actingAs($manager)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 100])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->firstOrFail();

        $this->actingAs($manager)
            ->getJson(route('pos.sales.refundable'))
            ->assertOk()
            ->assertJsonPath('sales.0.id', $sale->id);
    }
}
