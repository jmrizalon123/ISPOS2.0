<?php

namespace App\Domains\Crm\Services;

use App\Models\Customer;
use App\Models\Promotion;
use App\Models\Store;
use Illuminate\Support\Collection;

class PromotionApplicationService
{
    /** @param  list<array{product_id: string, category_id?: string|null, line_subtotal: string, line_total: string}>  $lines */
    public function resolveBestPromotion(Store $store, array $lines, string $subtotal, ?Customer $customer = null): ?array
    {
        $promotions = Promotion::query()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->with(['products:id', 'categories:id'])
            ->get()
            ->filter(fn (Promotion $promotion) => $promotion->isCurrentlyActive());

        if ($promotions->isEmpty()) {
            return null;
        }

        $best = null;
        $bestDiscount = '0.0000';

        foreach ($promotions as $promotion) {
            if (! $this->promotionAppliesToCart($promotion, $lines, $subtotal)) {
                continue;
            }

            $discount = $this->calculateDiscount($promotion, $lines, $subtotal);
            if (bccomp($discount, $bestDiscount, 4) > 0) {
                $bestDiscount = $discount;
                $best = $promotion;
            }
        }

        if (! $best || bccomp($bestDiscount, '0', 4) <= 0) {
            return null;
        }

        return [
            'promotion' => $best,
            'discount_total' => $bestDiscount,
        ];
    }

    /** @param  list<array{product_id: string, category_id?: string|null, line_subtotal: string, line_total: string}>  $lines */
    protected function promotionAppliesToCart(Promotion $promotion, array $lines, string $subtotal): bool
    {
        if ($promotion->min_purchase_amount !== null && bccomp($subtotal, (string) $promotion->min_purchase_amount, 4) < 0) {
            return false;
        }

        if ($promotion->applies_to === 'all') {
            return true;
        }

        if ($promotion->applies_to === 'products') {
            $productIds = $promotion->products->pluck('id');

            return collect($lines)->contains(fn ($line) => $productIds->contains($line['product_id']));
        }

        if ($promotion->applies_to === 'categories') {
            $categoryIds = $promotion->categories->pluck('id');

            return collect($lines)->contains(function ($line) use ($categoryIds) {
                return ! empty($line['category_id']) && $categoryIds->contains($line['category_id']);
            });
        }

        return false;
    }

    /** @param  list<array{product_id: string, category_id?: string|null, line_subtotal: string, line_total: string}>  $lines */
    public function calculateDiscount(Promotion $promotion, array $lines, string $subtotal): string
    {
        $applicableSubtotal = $subtotal;

        if ($promotion->applies_to === 'products') {
            $productIds = $promotion->products->pluck('id');
            $applicableSubtotal = collect($lines)
                ->filter(fn ($line) => $productIds->contains($line['product_id']))
                ->sum(fn ($line) => $line['line_subtotal']);
            $applicableSubtotal = (string) $applicableSubtotal;
        } elseif ($promotion->applies_to === 'categories') {
            $categoryIds = $promotion->categories->pluck('id');
            $applicableSubtotal = collect($lines)
                ->filter(fn ($line) => ! empty($line['category_id']) && $categoryIds->contains($line['category_id']))
                ->sum(fn ($line) => $line['line_subtotal']);
            $applicableSubtotal = (string) $applicableSubtotal;
        }

        if (bccomp($applicableSubtotal, '0', 4) <= 0) {
            return '0.0000';
        }

        if ($promotion->promo_type === 'percent_off') {
            $rate = bcdiv((string) $promotion->discount_value, '100', 6);

            return bcmul($applicableSubtotal, $rate, 4);
        }

        $fixed = (string) $promotion->discount_value;

        return bccomp($fixed, $applicableSubtotal, 4) > 0 ? $applicableSubtotal : $fixed;
    }

    public function membershipDiscount(?Customer $customer, string $subtotal): string
    {
        if (! $customer) {
            return '0.0000';
        }

        $membership = $customer->activeMembership();
        if (! $membership || (float) $membership->membershipPlan->discount_percent <= 0) {
            return '0.0000';
        }

        $rate = bcdiv((string) $membership->membershipPlan->discount_percent, '100', 6);

        return bcmul($subtotal, $rate, 4);
    }
}
