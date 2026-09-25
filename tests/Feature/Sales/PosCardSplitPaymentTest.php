<?php

namespace Tests\Feature\Sales;

use App\Domains\Accounting\Services\DefaultChartOfAccountsService;
use App\Domains\Sales\Services\PosShiftService;
use App\Models\GlAccountMapping;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\SalePayment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosCardSplitPaymentTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_full_card_checkout_persists_card_payment(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register, 500);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), [
                'payments' => [
                    ['payment_method' => 'card', 'amount' => 112, 'reference' => 'AUTH-99'],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);
        $this->assertSame('112.0000', (string) $sale->grand_total);

        $payment = SalePayment::query()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('card', $payment->payment_method);
        $this->assertSame('112.0000', (string) $payment->amount);
        $this->assertSame('AUTH-99', $payment->reference);

        $shift = $sale->posShift;
        $expected = app(PosShiftService::class)->expectedCashForShift($shift);
        $this->assertSame('500.0000', $expected);
    }

    public function test_split_cash_and_card_checkout_persists_both_payments(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register, 100);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), [
                'cash_tendered' => 60,
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 50],
                    ['payment_method' => 'card', 'amount' => 62, 'reference' => 'SPLIT-1'],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $this->assertSame(2, SalePayment::query()->where('sale_id', $sale->id)->count());
        $this->assertSame('50.0000', (string) SalePayment::query()->where('sale_id', $sale->id)->where('payment_method', 'cash')->value('amount'));
        $this->assertSame('62.0000', (string) SalePayment::query()->where('sale_id', $sale->id)->where('payment_method', 'card')->value('amount'));

        $expected = app(PosShiftService::class)->expectedCashForShift($sale->posShift);
        $this->assertSame('150.0000', $expected);
    }

    public function test_split_payments_must_equal_sale_total(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.checkout.store'), [
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 40],
                    ['payment_method' => 'card', 'amount' => 40],
                ],
            ])
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('payments');

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_card_sale_posts_to_card_clearing_gl_account(): void
    {
        ['cashier' => $cashier, 'store' => $store, 'register' => $register, 'tax' => $tax, 'company' => $company] = $this->createPosFixtures();
        $company->update(['enable_accounting' => true]);
        app(DefaultChartOfAccountsService::class)->seedForCompany($company, $cashier);

        $product = $this->createRetailProduct($company, $tax, 100);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->post(route('pos.cart.lines.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.checkout.store'), [
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 40],
                    ['payment_method' => 'card', 'amount' => 72],
                ],
                'cash_tendered' => 40,
            ])
            ->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $cardAccountId = GlAccountMapping::query()
            ->where('company_id', $company->id)
            ->where('mapping_key', GlAccountMapping::POS_CARD)
            ->value('chart_of_account_id');
        $cashAccountId = GlAccountMapping::query()
            ->where('company_id', $company->id)
            ->where('mapping_key', GlAccountMapping::POS_CASH)
            ->value('chart_of_account_id');

        $this->assertTrue(
            JournalEntryLine::query()
                ->where('chart_of_account_id', $cardAccountId)
                ->where('debit', '72.0000')
                ->exists(),
        );
        $this->assertTrue(
            JournalEntryLine::query()
                ->where('chart_of_account_id', $cashAccountId)
                ->where('debit', '40.0000')
                ->exists(),
        );
        $this->assertNotNull($sale);
    }
}
