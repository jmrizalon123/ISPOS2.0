<?php

namespace App\Domains\OnlineStore\Services;

use App\Domains\Sales\Services\PosCatalogService;
use App\Domains\Sales\Services\PosPricingService;
use App\Models\OnlineOrder;
use App\Models\OnlineStoreSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlineOrderService
{
    public function __construct(
        protected PosCatalogService $catalog,
        protected PosPricingService $pricing,
        protected OnlineStoreSettingService $settings,
        protected AuditLogger $auditLogger,
    ) {}

    public function paginate(
        User $user,
        ?string $search = null,
        ?string $companyId = null,
        ?string $storeId = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        return OnlineOrder::query()
            ->with([
                'store:id,store_name,store_code,company_id',
                'company:id,name,display_name',
                'lines',
            ])
            ->when(! $user->hasGlobalOrganizationAccess(), function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
                if (! $user->hasRole('Company Admin') && ! $user->hasRole('Super Admin')) {
                    $q->whereIn('store_id', $user->stores()->pluck('stores.id'));
                }
            })
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhere('guest_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function placeOrder(OnlineStoreSetting $setting, array $payload): OnlineOrder
    {
        $store = $setting->store;
        $this->assertStorefrontReady($setting, $store);

        $fulfillment = $payload['fulfillment_type'] ?? 'pickup';
        $this->assertFulfillmentAllowed($setting, $fulfillment);

        $linesInput = $payload['lines'] ?? [];
        if (! is_array($linesInput) || count($linesInput) === 0) {
            throw ValidationException::withMessages(['lines' => 'Your cart is empty.']);
        }

        $builtLines = [];
        $subtotal = '0.0000';
        $taxTotal = '0.0000';

        foreach ($linesInput as $index => $lineInput) {
            $productId = $lineInput['product_id'] ?? null;
            $product = $productId ? $this->catalog->findForStore($store, (string) $productId) : null;
            if (! $product) {
                throw ValidationException::withMessages(["lines.{$index}.product_id" => 'Product is unavailable.']);
            }

            $variant = null;
            if (! empty($lineInput['product_variant_id'])) {
                $variant = $product->variants->firstWhere('id', $lineInput['product_variant_id']);
                if (! $variant) {
                    throw ValidationException::withMessages(["lines.{$index}.product_variant_id" => 'Variant is unavailable.']);
                }
            }

            $qty = max(0.0001, (float) ($lineInput['qty'] ?? 1));
            if ($product->track_inventory) {
                $onHand = (float) ($product->getAttribute('store_qty') ?? $product->qty ?? 0);
                if ($onHand < $qty) {
                    throw ValidationException::withMessages(["lines.{$index}.qty" => "Insufficient stock for {$product->name}."]);
                }
            }

            $modifiers = $this->normalizeModifiers($product, $lineInput['modifiers'] ?? []);
            $basePrice = $this->pricing->resolveUnitPrice($product, $store, $variant);
            $calc = $this->pricing->calculateLine($product, $basePrice, $qty, $modifiers);

            $builtLines[] = [
                'product' => $product,
                'variant' => $variant,
                'qty' => $qty,
                'modifiers' => $modifiers,
                'calc' => $calc,
                'name' => $variant ? "{$product->name} — {$variant->name}" : $product->name,
                'sku' => $variant?->sku ?: $product->sku,
            ];

            $subtotal = bcadd($subtotal, $calc['line_subtotal'], 4);
            $taxTotal = bcadd($taxTotal, $calc['tax_amount'], 4);
        }

        $deliveryFee = '0.0000';
        if ($fulfillment === 'delivery') {
            $deliveryFee = $setting->resolvedDeliveryFee((float) $subtotal);
        }

        $grandTotal = bcadd(bcadd($subtotal, $taxTotal, 4), $deliveryFee, 4);
        $discountTotal = '0.0000';

        if ((float) $setting->min_order_amount > 0 && (float) $grandTotal < (float) $setting->min_order_amount) {
            throw ValidationException::withMessages([
                'cart' => 'Minimum order amount is '.number_format((float) $setting->min_order_amount, 2).'.',
            ]);
        }

        $paymentMethod = $payload['payment_method'] ?? 'cod';
        $allowedPayments = $setting->payment_methods ?: ['cod', 'pay_at_store'];
        if (! in_array($paymentMethod, $allowedPayments, true)) {
            throw ValidationException::withMessages(['payment_method' => 'Payment method is not accepted.']);
        }

        return DB::transaction(function () use (
            $setting,
            $store,
            $payload,
            $fulfillment,
            $builtLines,
            $subtotal,
            $taxTotal,
            $discountTotal,
            $deliveryFee,
            $grandTotal,
            $paymentMethod,
        ) {
            $order = OnlineOrder::create([
                'order_number' => $this->nextOrderNumber($store),
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'guest_name' => $payload['guest_name'],
                'guest_email' => $payload['guest_email'] ?? null,
                'guest_phone' => $payload['guest_phone'],
                'fulfillment_type' => $fulfillment,
                'status' => $setting->auto_accept_orders ? 'accepted' : 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'delivery_address_line_1' => $payload['delivery_address_line_1'] ?? null,
                'delivery_address_line_2' => $payload['delivery_address_line_2'] ?? null,
                'delivery_barangay' => $payload['delivery_barangay'] ?? null,
                'delivery_city' => $payload['delivery_city'] ?? null,
                'delivery_province' => $payload['delivery_province'] ?? null,
                'delivery_postal_code' => $payload['delivery_postal_code'] ?? null,
                'delivery_notes' => $payload['delivery_notes'] ?? null,
                'customer_notes' => $payload['customer_notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'delivery_fee' => $deliveryFee,
                'grand_total' => $grandTotal,
                'currency' => $store->currency ?: 'PHP',
                'accepted_at' => $setting->auto_accept_orders ? now() : null,
                'source' => 'web',
                'metadata' => [
                    'store_category' => $store->store_category,
                    'storefront_slug' => $setting->slug,
                ],
            ]);

            foreach ($builtLines as $index => $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'];

                $orderLine = $order->lines()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'line_number' => $index + 1,
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['calc']['unit_price'],
                    'line_subtotal' => $line['calc']['line_subtotal'],
                    'tax_amount' => $line['calc']['tax_amount'],
                    'line_total' => $line['calc']['line_total'],
                    'metadata' => ['base_unit_price' => $this->pricing->resolveUnitPrice($product, $store, $variant)],
                ]);

                foreach ($line['modifiers'] as $modifier) {
                    $orderLine->modifiers()->create([
                        'product_modifier_group_id' => $modifier['product_modifier_group_id'],
                        'product_modifier_option_id' => $modifier['product_modifier_option_id'],
                        'modifier_group_name' => $modifier['modifier_group_name'],
                        'option_name' => $modifier['option_name'],
                        'price_adjustment' => $modifier['price_adjustment'],
                    ]);
                }
            }

            return $order->fresh(['lines.modifiers', 'store']);
        });
    }

    public function updateStatus(OnlineOrder $order, string $status, User $user, ?string $reason = null): OnlineOrder
    {
        if (! in_array($status, OnlineOrder::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid status.']);
        }

        $before = $order->toArray();
        $data = ['status' => $status];

        match ($status) {
            'accepted' => $data = array_merge($data, [
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]),
            'rejected' => $data = array_merge($data, [
                'rejected_at' => now(),
                'rejected_by' => $user->id,
                'rejection_reason' => $reason,
            ]),
            'ready' => $data['ready_at'] = now(),
            'completed' => $data['completed_at'] = now(),
            'cancelled' => $data['cancelled_at'] = now(),
            default => null,
        };

        $order->update($data);
        $this->auditLogger->log('update', 'online_orders', OnlineOrder::class, $order->id, $before, $order->fresh()->toArray(), $user);

        return $order->fresh(['lines.modifiers', 'store']);
    }

    /** @return list<OnlineOrder> */
    public function pendingForStore(string $storeId, ?string $sinceId = null): array
    {
        return OnlineOrder::query()
            ->with(['lines.modifiers'])
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])
            ->when($sinceId, fn ($q) => $q->where('id', '>', $sinceId))
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->all();
    }

    public function pendingCountForStore(string $storeId): int
    {
        return OnlineOrder::query()
            ->where('store_id', $storeId)
            ->where('status', 'pending')
            ->count();
    }

    protected function assertStorefrontReady(OnlineStoreSetting $setting, Store $store): void
    {
        if (! $setting->is_published || $setting->status !== 'active') {
            throw ValidationException::withMessages(['store' => 'This online store is not available.']);
        }

        if (! $store->enable_online_ordering || ! $store->is_active || $store->status !== 'active') {
            throw ValidationException::withMessages(['store' => 'Online ordering is disabled for this store.']);
        }

        $company = $store->company;
        if ($company && $company->enable_ecommerce === false) {
            throw ValidationException::withMessages(['store' => 'E-commerce is disabled for this company.']);
        }

        if (! $setting->isAcceptingOrdersNow()) {
            throw ValidationException::withMessages(['store' => 'This store is currently closed for online orders.']);
        }
    }

    protected function assertFulfillmentAllowed(OnlineStoreSetting $setting, string $fulfillment): void
    {
        $ok = match ($fulfillment) {
            'pickup' => $setting->accept_pickup,
            'delivery' => $setting->accept_delivery,
            'dine_in' => $setting->accept_dine_in,
            default => false,
        };

        if (! $ok) {
            throw ValidationException::withMessages(['fulfillment_type' => 'Selected fulfillment option is not available.']);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $modifiers
     * @return list<array{product_modifier_group_id: string|null, product_modifier_option_id: string|null, modifier_group_name: string, option_name: string, price_adjustment: float|string}>
     */
    protected function normalizeModifiers(Product $product, array $modifiers): array
    {
        if (count($modifiers) === 0) {
            return [];
        }

        $normalized = [];
        foreach ($modifiers as $modifier) {
            $optionId = $modifier['product_modifier_option_id'] ?? null;
            $option = null;
            foreach ($product->modifierGroups as $group) {
                $found = $group->options->firstWhere('id', $optionId);
                if ($found) {
                    $option = $found;
                    $normalized[] = [
                        'product_modifier_group_id' => $group->id,
                        'product_modifier_option_id' => $found->id,
                        'modifier_group_name' => $group->name,
                        'option_name' => $found->name,
                        'price_adjustment' => $found->price_adjustment ?? 0,
                    ];
                    break;
                }
            }
            if (! $option && isset($modifier['option_name'])) {
                $normalized[] = [
                    'product_modifier_group_id' => $modifier['product_modifier_group_id'] ?? null,
                    'product_modifier_option_id' => $optionId,
                    'modifier_group_name' => $modifier['modifier_group_name'] ?? 'Modifier',
                    'option_name' => $modifier['option_name'],
                    'price_adjustment' => $modifier['price_adjustment'] ?? 0,
                ];
            }
        }

        return $normalized;
    }

    protected function nextOrderNumber(Store $store): string
    {
        $prefix = 'ON-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $store->store_code ?: 'ST') ?: 'ST', 0, 4));
        $latest = OnlineOrder::query()
            ->where('store_id', $store->id)
            ->where('order_number', 'like', $prefix.'-%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%06d', $prefix, $seq);
    }
}
