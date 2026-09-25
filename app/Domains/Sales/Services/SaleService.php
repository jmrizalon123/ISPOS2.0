<?php

namespace App\Domains\Sales\Services;

use App\Models\PosShift;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\Store;
use App\Models\User;
use App\Domains\Accounting\Services\GlPostingService;
use App\Domains\Crm\Services\LoyaltyTransactionService;
use App\Domains\Crm\Services\PromotionService;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\Kds\Services\KitchenTicketService;
use App\Models\Promotion;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected PosCartService $cart,
        protected PosContextService $context,
        protected SaleInventoryService $saleInventory,
        protected LoyaltyTransactionService $loyaltyTransactions,
        protected PromotionService $promotionService,
        protected GlPostingService $glPosting,
        protected KitchenTicketService $kitchenTickets,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<array{payment_method: string, amount: float|string, reference?: string|null}>|null  $payments
     */
    public function checkout(
        User $user,
        Store $store,
        Register $register,
        PosShift $shift,
        ?float $cashTendered = null,
        ?array $payments = null,
    ): Sale {
        $summary = $this->cart->summary($store);
        $lines = $summary['lines'] ?? [];

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        $grandTotal = (float) $summary['grand_total'];
        $normalizedPayments = $this->normalizePayments($payments, $grandTotal, $cashTendered);
        $cashApplied = $this->paymentAmountForMethod($normalizedPayments, 'cash');
        $effectiveCashTendered = $cashTendered;

        if ($cashApplied > 0) {
            $effectiveCashTendered ??= $cashApplied;
            if ($effectiveCashTendered < $cashApplied) {
                throw ValidationException::withMessages([
                    'cash_tendered' => 'Cash tendered must cover the cash portion of the payment.',
                ]);
            }
        }

        $customerId = $this->cart->customerId();
        $promotionId = $summary['promotion']['id'] ?? null;

        return DB::transaction(function () use (
            $user,
            $store,
            $register,
            $shift,
            $summary,
            $lines,
            $normalizedPayments,
            $effectiveCashTendered,
            $grandTotal,
            $cashApplied,
            $customerId,
            $promotionId,
        ) {
            $saleNumber = $this->nextSaleNumber($store);

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'register_id' => $register->id,
                'pos_shift_id' => $shift->id,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'promotion_id' => $promotionId,
                'status' => 'completed',
                'subtotal' => $summary['subtotal'],
                'tax_total' => $summary['tax_total'],
                'discount_total' => $summary['discount_total'],
                'grand_total' => $summary['grand_total'],
                'completed_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($lines as $index => $line) {
                $product = Product::query()->find($line['product_id']);

                $saleLine = $sale->lines()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'line_number' => $index + 1,
                    'sku' => $line['sku'] ?? null,
                    'name' => $line['name'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'line_subtotal' => $line['line_subtotal'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                    'metadata' => [
                        'base_unit_price' => $line['base_unit_price'] ?? null,
                    ],
                ]);

                foreach ($line['modifiers'] ?? [] as $modifier) {
                    $saleLine->modifiers()->create([
                        'product_modifier_group_id' => $modifier['product_modifier_group_id'],
                        'product_modifier_option_id' => $modifier['product_modifier_option_id'],
                        'modifier_group_name' => $modifier['modifier_group_name'],
                        'option_name' => $modifier['option_name'],
                        'price_adjustment' => $modifier['price_adjustment'],
                    ]);
                }

                foreach ($line['components'] ?? [] as $component) {
                    $saleLine->components()->create([
                        'component_product_id' => $component['component_product_id'],
                        'component_name' => $component['component_name'],
                        'quantity' => $component['quantity'],
                        'is_optional' => $component['is_optional'],
                        'included' => $component['included'],
                    ]);
                }
            }

            foreach ($normalizedPayments as $payment) {
                $sale->payments()->create([
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            $this->saleInventory->deductForSale($sale->fresh(['lines.product.ingredients.ingredientProduct', 'lines.components']), $user);

            if ($promotionId) {
                $promotion = Promotion::query()->find($promotionId);
                if ($promotion) {
                    $this->promotionService->incrementUsage($promotion);
                }
            }

            $this->loyaltyTransactions->earnForSale($sale->fresh(), $user);
            $this->glPosting->postSale($sale->fresh(['payments']), $user);

            $this->cart->clear();

            $this->kitchenTickets->createFromSale($sale->fresh(['lines.product']));

            $change = '0.0000';
            if ($cashApplied > 0 && $effectiveCashTendered !== null) {
                $change = bcsub((string) $effectiveCashTendered, (string) $cashApplied, 4);
            }

            $this->auditLogger->log('create', 'sales', Sale::class, $sale->id, null, [
                'sale_number' => $sale->sale_number,
                'grand_total' => $sale->grand_total,
                'payments' => $normalizedPayments,
                'cash_tendered' => $effectiveCashTendered,
                'change' => $change,
            ], $user);

            return $sale->fresh(['lines.modifiers', 'lines.components', 'payments']);
        });
    }

    /**
     * @param  list<array{payment_method?: string, amount?: float|string, reference?: string|null}>|null  $payments
     * @return list<array{payment_method: string, amount: string, reference: string|null}>
     */
    protected function normalizePayments(?array $payments, float $grandTotal, ?float $cashTendered): array
    {
        if ($payments === null || count($payments) === 0) {
            if ($cashTendered === null) {
                throw ValidationException::withMessages(['cash_tendered' => 'Cash tendered is required.']);
            }

            if ($cashTendered < $grandTotal) {
                throw ValidationException::withMessages(['cash_tendered' => 'Insufficient cash tendered.']);
            }

            return [[
                'payment_method' => 'cash',
                'amount' => number_format($grandTotal, 4, '.', ''),
                'reference' => null,
            ]];
        }

        $normalized = [];
        $sum = '0.0000';

        foreach ($payments as $index => $payment) {
            $method = strtolower((string) ($payment['payment_method'] ?? ''));
            if (! in_array($method, ['cash', 'card'], true)) {
                throw ValidationException::withMessages([
                    "payments.{$index}.payment_method" => 'Payment method must be cash or card.',
                ]);
            }

            $amount = number_format((float) ($payment['amount'] ?? 0), 4, '.', '');
            if (bccomp($amount, '0', 4) !== 1) {
                throw ValidationException::withMessages([
                    "payments.{$index}.amount" => 'Payment amount must be greater than zero.',
                ]);
            }

            $reference = isset($payment['reference']) ? trim((string) $payment['reference']) : null;
            if ($reference === '') {
                $reference = null;
            }

            $normalized[] = [
                'payment_method' => $method,
                'amount' => $amount,
                'reference' => $reference,
            ];
            $sum = bcadd($sum, $amount, 4);
        }

        if (bccomp($sum, number_format($grandTotal, 4, '.', ''), 4) !== 0) {
            throw ValidationException::withMessages([
                'payments' => 'Payment amounts must equal the sale total.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array{payment_method: string, amount: string, reference: string|null}>  $payments
     */
    protected function paymentAmountForMethod(array $payments, string $method): float
    {
        $total = '0.0000';
        foreach ($payments as $payment) {
            if ($payment['payment_method'] === $method) {
                $total = bcadd($total, $payment['amount'], 4);
            }
        }

        return (float) $total;
    }

    public function void(User $user, Sale $sale): Sale
    {
        if ($sale->isVoided()) {
            throw ValidationException::withMessages(['sale' => 'Sale is already voided.']);
        }

        if ($sale->isRefunded()) {
            throw ValidationException::withMessages(['sale' => 'Refunded sales cannot be voided.']);
        }

        DB::transaction(function () use ($sale, $user) {
            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->saleInventory->reverseForSale($sale, $user);
            $this->loyaltyTransactions->reverseForSale($sale, $user);
            $this->glPosting->reverseSale($sale->fresh(), $user);
            $this->kitchenTickets->cancelForSale($sale);
        });

        $this->auditLogger->log('void', 'sales', Sale::class, $sale->id, ['status' => 'completed'], ['status' => 'voided'], $user);

        return $sale->fresh();
    }

    public function refund(User $user, Sale $sale, PosShift $refundShift, string $reason): Sale
    {
        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['sale' => 'Only completed sales can be refunded.']);
        }

        if ($sale->isRefunded() || $sale->refund()->exists()) {
            throw ValidationException::withMessages(['sale' => 'Sale is already refunded.']);
        }

        if ($sale->store_id !== $refundShift->store_id) {
            throw ValidationException::withMessages(['sale' => 'Sale does not belong to this store.']);
        }

        if (! $refundShift->isOpen()) {
            throw ValidationException::withMessages(['shift' => 'An open shift is required to issue a refund.']);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Refund reason is required.']);
        }

        DB::transaction(function () use ($sale, $user, $refundShift, $reason) {
            $sale->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refunded_by' => $user->id,
                'refund_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            SaleRefund::create([
                'sale_id' => $sale->id,
                'company_id' => $sale->company_id,
                'store_id' => $sale->store_id,
                'pos_shift_id' => $refundShift->id,
                'user_id' => $user->id,
                'amount' => $sale->grand_total,
                'payment_method' => 'cash',
                'reason' => $reason,
                'refunded_at' => now(),
            ]);

            $this->saleInventory->reverseForSale($sale, $user, 'refund', 'Sale refunded: '.$reason);
            $this->loyaltyTransactions->reverseForSale($sale, $user);
            $this->glPosting->reverseSale($sale->fresh(), $user);
            $this->kitchenTickets->cancelForSale($sale);
        });

        $this->auditLogger->log('refund', 'sales', Sale::class, $sale->id, ['status' => 'completed'], [
            'status' => 'refunded',
            'reason' => $reason,
            'amount' => $sale->grand_total,
        ], $user);

        return $sale->fresh(['refund']);
    }

    protected function nextSaleNumber(Store $store): string
    {
        $count = Sale::query()
            ->where('store_id', $store->id)
            ->whereDate('completed_at', today())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $store->store_code, now()->format('Ymd'), $seq);
    }
}
