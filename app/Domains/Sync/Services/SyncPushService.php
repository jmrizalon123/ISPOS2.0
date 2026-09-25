<?php

namespace App\Domains\Sync\Services;

use App\Domains\Accounting\Services\GlPostingService;
use App\Domains\Crm\Services\LoyaltyTransactionService;
use App\Domains\Crm\Services\PromotionService;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\Kds\Services\KitchenTicketService;
use App\Domains\Sales\Services\PosCatalogService;
use App\Models\PosDevice;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncPushService
{
    public function __construct(
        protected SaleInventoryService $saleInventory,
        protected LoyaltyTransactionService $loyaltyTransactions,
        protected PromotionService $promotionService,
        protected GlPostingService $glPosting,
        protected AuditLogger $auditLogger,
        protected SyncLogService $syncLogs,
        protected PosCatalogService $catalog,
        protected KitchenTicketService $kitchenTickets,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $sales
     * @return array{accepted: list<array<string, string>>, duplicates: list<array<string, string>>, failed: list<array<string, string>>}
     */
    public function pushSales(PosDevice $device, User $user, array $sales): array
    {
        $accepted = [];
        $duplicates = [];
        $failed = [];

        foreach ($sales as $index => $payload) {
            try {
                $result = $this->ingestSale($device, $user, $payload);
                if ($result['duplicate']) {
                    $duplicates[] = [
                        'client_uuid' => $result['client_uuid'],
                        'sale_id' => $result['sale_id'],
                        'sale_number' => $result['sale_number'],
                    ];
                } else {
                    $accepted[] = [
                        'client_uuid' => $result['client_uuid'],
                        'sale_id' => $result['sale_id'],
                        'sale_number' => $result['sale_number'],
                    ];
                }
            } catch (\Throwable $exception) {
                $failed[] = [
                    'index' => (string) $index,
                    'client_uuid' => (string) ($payload['client_uuid'] ?? ''),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $status = count($failed) === 0 ? 'success' : (count($accepted) + count($duplicates) > 0 ? 'partial' : 'failed');

        $device->update(['last_sync_at' => now()]);

        $this->syncLogs->record($device, 'push', $status, count($accepted) + count($duplicates), [
            'accepted' => count($accepted),
            'duplicates' => count($duplicates),
            'failed' => count($failed),
        ], count($failed) ? $failed[0]['message'] ?? null : null);

        return compact('accepted', 'duplicates', 'failed');
    }

    /** @param  array<string, mixed>  $payload */
    protected function ingestSale(PosDevice $device, User $user, array $payload): array
    {
        $clientUuid = $payload['client_uuid'] ?? null;
        if (! is_string($clientUuid) || $clientUuid === '') {
            throw ValidationException::withMessages(['client_uuid' => 'Client UUID is required.']);
        }

        $existing = Sale::query()->where('uuid', $clientUuid)->first();
        if ($existing) {
            return [
                'duplicate' => true,
                'client_uuid' => $clientUuid,
                'sale_id' => $existing->id,
                'sale_number' => $existing->sale_number,
            ];
        }

        $shift = PosShift::query()->with('register.store')->find($payload['pos_shift_id'] ?? null);
        if (! $shift) {
            throw ValidationException::withMessages(['pos_shift_id' => 'Shift not found.']);
        }

        if ($shift->register_id !== $device->register_id) {
            throw ValidationException::withMessages(['pos_shift_id' => 'Shift does not belong to this device register.']);
        }

        $store = $shift->register->store;
        $lines = $payload['lines'] ?? [];
        if (! is_array($lines) || count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'At least one sale line is required.']);
        }

        return DB::transaction(function () use ($device, $user, $payload, $clientUuid, $shift, $store, $lines) {
            $sale = Sale::create([
                'uuid' => $clientUuid,
                'sale_number' => $this->nextSaleNumber($store, $payload['completed_at'] ?? null),
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'register_id' => $device->register_id,
                'pos_device_id' => $device->id,
                'pos_shift_id' => $shift->id,
                'user_id' => $user->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'promotion_id' => $payload['promotion_id'] ?? null,
                'status' => 'completed',
                'sync_source' => 'offline_sync',
                'subtotal' => $payload['subtotal'],
                'tax_total' => $payload['tax_total'],
                'discount_total' => $payload['discount_total'] ?? 0,
                'grand_total' => $payload['grand_total'],
                'completed_at' => $payload['completed_at'] ?? now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($lines as $index => $line) {
                $product = Product::query()->find($line['product_id'] ?? null);

                $saleLine = $sale->lines()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'line_number' => $index + 1,
                    'sku' => $line['sku'] ?? $product?->sku,
                    'name' => $line['name'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'line_subtotal' => $line['line_subtotal'],
                    'tax_amount' => $line['tax_amount'] ?? 0,
                    'line_total' => $line['line_total'],
                    'metadata' => $line['metadata'] ?? null,
                ]);

                foreach ($line['modifiers'] ?? [] as $modifier) {
                    $saleLine->modifiers()->create([
                        'product_modifier_group_id' => $modifier['product_modifier_group_id'],
                        'product_modifier_option_id' => $modifier['product_modifier_option_id'],
                        'modifier_group_name' => $modifier['modifier_group_name'],
                        'option_name' => $modifier['option_name'],
                        'price_adjustment' => $modifier['price_adjustment'] ?? 0,
                    ]);
                }

                foreach ($line['components'] ?? [] as $component) {
                    $saleLine->components()->create([
                        'component_product_id' => $component['component_product_id'],
                        'component_name' => $component['component_name'],
                        'quantity' => $component['quantity'],
                        'is_optional' => $component['is_optional'] ?? false,
                        'included' => $component['included'] ?? true,
                    ]);
                }
            }

            $payments = $payload['payments'] ?? null;
            if (is_array($payments) && count($payments) > 0) {
                foreach ($payments as $payment) {
                    $sale->payments()->create([
                        'payment_method' => $payment['method'] ?? $payment['payment_method'] ?? 'cash',
                        'amount' => $payment['amount'] ?? $payload['grand_total'],
                        'reference' => $payment['reference'] ?? null,
                        'paid_at' => $payload['completed_at'] ?? now(),
                    ]);
                }
            } else {
                $payment = $payload['payment'] ?? ['method' => 'cash', 'amount' => $payload['grand_total']];
                $sale->payments()->create([
                    'payment_method' => $payment['method'] ?? 'cash',
                    'amount' => $payment['amount'] ?? $payload['grand_total'],
                    'reference' => $payment['reference'] ?? null,
                    'paid_at' => $payload['completed_at'] ?? now(),
                ]);
            }

            $this->saleInventory->deductForSale(
                $sale->fresh(['lines.product.ingredients.ingredientProduct', 'lines.components']),
                $user,
            );

            if ($sale->promotion_id) {
                $promotion = Promotion::query()->find($sale->promotion_id);
                if ($promotion) {
                    $this->promotionService->incrementUsage($promotion);
                }
            }

            $this->loyaltyTransactions->earnForSale($sale->fresh(), $user);
            $this->glPosting->postSale($sale->fresh(['payments']), $user);
            $this->kitchenTickets->createFromSale($sale->fresh(['lines.product']));

            $this->auditLogger->log('create', 'sales', Sale::class, $sale->id, null, [
                'sale_number' => $sale->sale_number,
                'grand_total' => $sale->grand_total,
                'sync_source' => 'offline_sync',
                'pos_device_id' => $device->id,
            ], $user);

            return [
                'duplicate' => false,
                'client_uuid' => $clientUuid,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
            ];
        });
    }

    protected function nextSaleNumber(Store $store, mixed $completedAt = null): string
    {
        $date = $completedAt ? \Illuminate\Support\Carbon::parse($completedAt) : now();

        $count = Sale::query()
            ->where('store_id', $store->id)
            ->whereDate('completed_at', $date->toDateString())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $store->store_code, $date->format('Ymd'), $seq);
    }
}
