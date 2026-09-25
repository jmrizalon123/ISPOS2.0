<?php

namespace Tests\Feature\Sales;

use App\Models\PosShift;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosShiftTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_open_shift_required_for_cart_actions(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax);

        $this->actingAs($cashier)
            ->post(route('pos.session.store'), [
                'store_id' => $store->id,
                'register_id' => $register->id,
            ])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id])
            ->assertForbidden();
    }

    public function test_only_one_open_shift_per_register(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register] = $this->createPosFixtures();

        $this->startPosSession($cashier, $store, $register, 500);

        $this->assertSame(1, PosShift::query()->where('register_id', $register->id)->where('status', 'open')->count());

        $this->actingAs($cashier)
            ->post(route('pos.shift.open'), ['opening_float' => 0])
            ->assertRedirect(route('pos.index'));

        $this->assertSame(1, PosShift::query()->where('register_id', $register->id)->where('status', 'open')->count());
    }
}
