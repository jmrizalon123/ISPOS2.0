<?php

namespace Tests\Feature\Kds;

use App\Models\KitchenTicket;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class KitchenTicketStatusTest extends TestCase
{
    use CreatesPosFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_kitchen_staff_can_advance_ticket_status(): void
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

        $ticket = KitchenTicket::query()->first();
        $this->assertNotNull($ticket);

        $kitchenStaff = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $kitchenStaff->assignRole('Kitchen Staff');
        $kitchenStaff->stores()->sync([$store->id]);

        $this->actingAs($kitchenStaff)
            ->post(route('kds.session.store'), ['store_id' => $store->id])
            ->assertRedirect(route('kds.index'));

        $this->actingAs($kitchenStaff)
            ->patchJson(route('kds.tickets.update', $ticket), ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('ticket.status', 'preparing');

        $this->actingAs($kitchenStaff)
            ->patchJson(route('kds.tickets.update', $ticket), ['status' => 'ready'])
            ->assertOk()
            ->assertJsonPath('ticket.status', 'ready');

        $this->actingAs($kitchenStaff)
            ->patchJson(route('kds.tickets.update', $ticket), ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('kitchen_tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
        ]);
    }

    public function test_kitchen_staff_cannot_update_ticket_for_other_store(): void
    {
        $store = Store::factory()->create(['status' => 'active']);
        $otherStore = Store::factory()->create([
            'company_id' => $store->company_id,
            'status' => 'active',
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $store->company_id,
            'store_id' => $otherStore->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $ticket = KitchenTicket::factory()->create([
            'company_id' => $store->company_id,
            'store_id' => $otherStore->id,
            'sale_id' => $sale->id,
        ]);

        $kitchenStaff = User::factory()->create([
            'company_id' => $store->company_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $kitchenStaff->assignRole('Kitchen Staff');
        $kitchenStaff->stores()->sync([$store->id]);

        $this->actingAs($kitchenStaff)
            ->post(route('kds.session.store'), ['store_id' => $store->id])
            ->assertRedirect(route('kds.index'));

        $this->actingAs($kitchenStaff)
            ->patchJson(route('kds.tickets.update', $ticket), ['status' => 'preparing'])
            ->assertForbidden();
    }
}
