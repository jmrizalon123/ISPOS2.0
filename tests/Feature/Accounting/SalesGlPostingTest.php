<?php

namespace Tests\Feature\Accounting;

use App\Domains\Accounting\Services\DefaultChartOfAccountsService;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Sale;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class SalesGlPostingTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos_checkout_posts_gl_entry_when_accounting_enabled(): void
    {
        $fixtures = $this->createPosFixtures();
        extract($fixtures);

        $company->update(['enable_accounting' => true]);
        app(DefaultChartOfAccountsService::class)->seedForCompany($company, $cashier);

        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->where('store_id', $store->id)->first();
        $this->assertNotNull($sale);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'status' => 'posted',
        ]);
    }

    public function test_voiding_sale_creates_reversal_journal_entry(): void
    {
        $fixtures = $this->createPosFixtures();
        extract($fixtures);

        $cashier->givePermissionTo('pos.void');

        $company->update(['enable_accounting' => true]);
        app(DefaultChartOfAccountsService::class)->seedForCompany($company, $cashier);

        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), ['cash_tendered' => 200]);

        $sale = Sale::query()->where('store_id', $store->id)->first();

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect(route('pos.index'));

        $this->assertSame(2, JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('status', 'posted')
            ->count());
    }
}
