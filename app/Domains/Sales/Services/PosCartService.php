<?php

namespace App\Domains\Sales\Services;

use App\Domains\Crm\Services\PromotionApplicationService;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosCartService
{
    public const SESSION_KEY = 'pos_cart';

    public const CUSTOMER_SESSION_KEY = 'pos_customer_id';

    public function __construct(
        protected PosPricingService $pricing,
        protected PosCatalogService $catalog,
        protected PromotionApplicationService $promotionApplication,
    ) {}

    /** @return list<array<string, mixed>> */
    public function lines(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::CUSTOMER_SESSION_KEY);
    }

    public function customerId(): ?string
    {
        return Session::get(self::CUSTOMER_SESSION_KEY);
    }

    public function setCustomer(?string $customerId): void
    {
        if ($customerId) {
            Session::put(self::CUSTOMER_SESSION_KEY, $customerId);
        } else {
            Session::forget(self::CUSTOMER_SESSION_KEY);
        }
    }

    public function resolveCustomer(Store $store): ?Customer
    {
        $customerId = $this->customerId();
        if (! $customerId) {
            return null;
        }

        return Customer::query()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->with(['memberships' => fn ($q) => $q->where('status', 'active')->with('membershipPlan')])
            ->find($customerId);
    }

    /** @return array<string, mixed> */
    public function summary(Store $store): array
    {
        $customer = $this->resolveCustomer($store);
        $lines = $this->lines();
        $pricedLines = array_map(fn ($line) => $this->priceLine($line, $store, $customer), $lines);

        $totals = $this->pricing->summarizeCart($pricedLines);
        $membershipDiscount = $this->promotionApplication->membershipDiscount($customer, $totals['subtotal']);
        $subtotalAfterMembership = bcsub($totals['subtotal'], $membershipDiscount, 4);

        $promotionLines = array_map(fn ($line) => [
            'product_id' => $line['product_id'],
            'category_id' => $line['category_id'] ?? null,
            'line_subtotal' => $line['line_subtotal'],
            'line_total' => $line['line_total'],
        ], $pricedLines);

        $promotionResult = $this->promotionApplication->resolveBestPromotion(
            $store,
            $promotionLines,
            $subtotalAfterMembership,
            $customer,
        );

        $promotionDiscount = $promotionResult['discount_total'] ?? '0.0000';
        $discountTotal = bcadd($membershipDiscount, $promotionDiscount, 4);
        $grandTotal = bcsub($totals['grand_total'], $discountTotal, 4);
        if (bccomp($grandTotal, '0', 4) < 0) {
            $grandTotal = '0.0000';
        }

        return array_merge($totals, [
            'lines' => $pricedLines,
            'discount_total' => $discountTotal,
            'membership_discount' => $membershipDiscount,
            'promotion_discount' => $promotionDiscount,
            'grand_total' => $grandTotal,
            'customer' => $customer ? [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->displayName(),
                'loyalty_points' => (string) $customer->loyalty_points,
                'membership' => $customer->activeMembership()?->membershipPlan?->only(['id', 'name', 'plan_code']),
            ] : null,
            'promotion' => $promotionResult ? [
                'id' => $promotionResult['promotion']->id,
                'promo_code' => $promotionResult['promotion']->promo_code,
                'name' => $promotionResult['promotion']->name,
            ] : null,
        ]);
    }

    /**
     * @param  array{product_id: string, product_variant_id?: string|null, qty?: float, modifier_option_ids?: list<string>, component_inclusions?: array<string, bool>}  $input
     */
    public function addLine(Store $store, array $input): array
    {
        $product = $this->catalog->findForStore($store, $input['product_id']);
        if (! $product) {
            throw ValidationException::withMessages(['product_id' => 'Product not found.']);
        }

        $variant = null;
        if (! empty($input['product_variant_id'])) {
            $variant = $product->variants->firstWhere('id', $input['product_variant_id'])
                ?? ProductVariant::query()->where('product_id', $product->id)->find($input['product_variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages(['product_variant_id' => 'Invalid variant.']);
            }
        } elseif ($product->has_variants && $product->variants->isNotEmpty()) {
            throw ValidationException::withMessages(['product_variant_id' => 'Please select a variant.']);
        }

        $modifierOptionIds = $input['modifier_option_ids'] ?? [];
        $modifiers = $this->validateAndBuildModifiers($product, $modifierOptionIds);

        $components = $this->validateAndBuildComponents($product, $input['component_inclusions'] ?? []);

        $qty = max(0.0001, (float) ($input['qty'] ?? 1));
        $basePrice = $this->pricing->resolveUnitPrice($product, $store, $variant);

        $lineKey = (string) Str::ulid();
        $line = [
            'key' => $lineKey,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'sku' => $variant?->sku ?? $product->sku,
            'name' => $variant ? "{$product->name} ({$variant->name})" : $product->name,
            'qty' => $qty,
            'base_unit_price' => $basePrice,
            'modifiers' => $modifiers,
            'components' => $components,
        ];

        $lines = $this->lines();
        $lines[] = $line;
        Session::put(self::SESSION_KEY, $lines);

        return $this->summary($store);
    }

    public function updateLineQty(Store $store, string $lineKey, float $qty): array
    {
        if ($qty <= 0) {
            return $this->removeLine($store, $lineKey);
        }

        $lines = collect($this->lines())->map(function ($line) use ($lineKey, $qty) {
            if ($line['key'] === $lineKey) {
                $line['qty'] = $qty;
            }

            return $line;
        })->all();

        Session::put(self::SESSION_KEY, $lines);

        return $this->summary($store);
    }

    public function removeLine(Store $store, string $lineKey): array
    {
        $lines = collect($this->lines())->reject(fn ($line) => $line['key'] === $lineKey)->values()->all();
        Session::put(self::SESSION_KEY, $lines);

        return $this->summary($store);
    }

    /** @param  list<string>  $optionIds
     * @return list<array<string, mixed>>
     */
    protected function validateAndBuildModifiers(Product $product, array $optionIds): array
    {
        $groups = $product->modifierGroups()->with('options')->where('status', 'active')->get();
        if ($groups->isEmpty()) {
            return [];
        }

        $options = ProductModifierOption::query()
            ->with('modifierGroup')
            ->whereIn('id', $optionIds)
            ->get()
            ->keyBy('id');

        foreach ($groups as $group) {
            $selected = collect($optionIds)
                ->map(fn ($id) => $options->get($id))
                ->filter(fn ($opt) => $opt && $opt->product_modifier_group_id === $group->id);

            $count = $selected->count();
            $min = $group->is_required ? max(1, (int) $group->min_selections) : (int) $group->min_selections;
            $max = $group->max_selections;

            if ($group->selection_type === 'single' && $count > 1) {
                throw ValidationException::withMessages(['modifier_option_ids' => "Select only one option for {$group->name}."]);
            }

            if ($count < $min) {
                throw ValidationException::withMessages(['modifier_option_ids' => "{$group->name} requires at least {$min} selection(s)."]);
            }

            if ($max && $count > $max) {
                throw ValidationException::withMessages(['modifier_option_ids' => "{$group->name} allows at most {$max} selection(s)."]);
            }
        }

        return collect($optionIds)->map(function ($id) use ($options) {
            $opt = $options->get($id);
            if (! $opt) {
                throw ValidationException::withMessages(['modifier_option_ids' => 'Invalid modifier option.']);
            }

            return [
                'product_modifier_group_id' => $opt->product_modifier_group_id,
                'product_modifier_option_id' => $opt->id,
                'modifier_group_name' => $opt->modifierGroup->name,
                'option_name' => $opt->name,
                'price_adjustment' => (string) $opt->price_adjustment,
            ];
        })->values()->all();
    }

    /** @param  array<string, bool>  $inclusions
     * @return list<array<string, mixed>>
     */
    protected function validateAndBuildComponents(Product $product, array $inclusions): array
    {
        if (! $product->has_components) {
            return [];
        }

        return ProductComponent::query()
            ->with('componentProduct:id,name')
            ->where('product_id', $product->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function (ProductComponent $component) use ($inclusions) {
                $included = $component->is_optional
                    ? (bool) ($inclusions[$component->id] ?? false)
                    : true;

                if (! $component->is_optional && ! $included) {
                    throw ValidationException::withMessages(['component_inclusions' => 'Required components cannot be removed.']);
                }

                return [
                    'product_component_id' => $component->id,
                    'component_product_id' => $component->component_product_id,
                    'component_name' => $component->componentProduct?->name ?? 'Component',
                    'quantity' => (string) $component->quantity,
                    'is_optional' => $component->is_optional,
                    'included' => $included,
                ];
            })
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $line */
    protected function priceLine(array $line, Store $store, ?Customer $customer = null): array
    {
        $product = Product::query()->with('tax')->find($line['product_id']);
        if (! $product) {
            return array_merge($line, [
                'category_id' => null,
                'unit_price' => '0',
                'line_subtotal' => '0',
                'tax_amount' => '0',
                'line_total' => '0',
            ]);
        }

        $priceGroupId = $customer?->price_group_id;
        $membership = $customer?->activeMembership();
        if ($membership?->membershipPlan?->price_group_id) {
            $priceGroupId = $membership->membershipPlan->price_group_id;
        }

        $variant = ! empty($line['product_variant_id'])
            ? ProductVariant::query()->find($line['product_variant_id'])
            : null;

        $baseUnitPrice = $this->pricing->resolveUnitPrice($product, $store, $variant, $priceGroupId);

        $priced = $this->pricing->calculateLine(
            $product,
            $baseUnitPrice,
            (float) $line['qty'],
            $line['modifiers'] ?? [],
        );

        return array_merge($line, $priced, [
            'category_id' => $product->category_id,
            'base_unit_price' => $baseUnitPrice,
        ]);
    }
}
