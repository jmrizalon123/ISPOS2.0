<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosReceiptTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_checkout_flashes_print_sale_id_and_receipt_page_renders(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $store->update([
            'receipt_header' => 'Demo Header',
            'receipt_footer' => 'Come again soon!',
            'address_line_1' => '123 Main St',
            'address_line_2' => null,
            'barangay' => null,
            'city' => 'Manila',
            'province' => null,
            'postal_code' => null,
        ]);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHas('print_sale_id');

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);
        $this->assertSame(session('print_sale_id'), $sale->id);

        $this->actingAs($cashier)
            ->get(route('pos.sales.receipt', ['sale' => $sale, 'change' => '88']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('POS/Receipt')
                ->where('receipt.sale.sale_number', $sale->sale_number)
                ->where('receipt.branding.header', 'Demo Header')
                ->where('receipt.branding.footer', 'Come again soon!')
                ->where('receipt.store.address', '123 Main St, Manila')
                ->where('change', '88.0000')
                ->has('receipt.lines', 1)
                ->has('receipt.payments', 1)
            );

        $this->actingAs($cashier)
            ->get(route('pos.sales.receipt.print', ['sale' => $sale, 'change' => '88', 'embedded' => 1]))
            ->assertOk()
            ->assertSee($sale->sale_number, false)
            ->assertSee('Demo Header', false)
            ->assertSee('Come again soon!', false);

        $this->actingAs($cashier)
            ->get(route('pos.sales.receipt.text', ['sale' => $sale, 'change' => '88']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee($sale->sale_number, false)
            ->assertSee('TOTAL', false);
    }

    public function test_receipt_forbidden_for_user_without_store_access(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);
        $this->actingAs($cashier)->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);
        $this->actingAs($cashier)->post(route('pos.checkout.store'), ['cash_tendered' => 200]);

        $sale = Sale::query()->first();

        $outsider = \App\Models\User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $outsider->assignRole('Cashier');

        $this->actingAs($outsider)
            ->get(route('pos.sales.receipt', $sale))
            ->assertForbidden();
    }
}
