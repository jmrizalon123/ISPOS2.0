<?php

namespace App\Domains\Sales\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Tax;

class PosPricingService
{
    public function resolveUnitPrice(Product $product, Store $store, ?ProductVariant $variant = null, ?string $priceGroupId = null): string
    {
        $priceGroupId = $priceGroupId ?: $store->price_group_id;
        if ($priceGroupId) {
            $priceRow = $product->prices()
                ->where('price_group_id', $priceGroupId)
                ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id))
                ->when(! $variant, fn ($q) => $q->whereNull('product_variant_id'))
                ->value('price');

            if ($priceRow !== null) {
                return (string) $priceRow;
            }
        }

        if ($variant) {
            return (string) ($variant->selling_price ?: $product->base_price);
        }

        return (string) $product->base_price;
    }

    /**
     * @param  list<array{price_adjustment: float|string}>  $modifiers
     * @return array{unit_price: string, line_subtotal: string, tax_amount: string, line_total: string}
     */
    public function calculateLine(
        Product $product,
        string $baseUnitPrice,
        float $qty,
        array $modifiers = [],
    ): array {
        $modifierTotal = collect($modifiers)->sum(fn ($m) => (float) ($m['price_adjustment'] ?? 0));
        $unitPrice = bcadd((string) $baseUnitPrice, (string) $modifierTotal, 4);
        $lineSubtotal = bcmul($unitPrice, (string) $qty, 4);

        $tax = $product->relationLoaded('tax') ? $product->tax : $product->tax()->first();
        $taxAmount = $this->calculateTax($lineSubtotal, $tax);

        if ($tax && $tax->is_inclusive) {
            $lineTotal = $lineSubtotal;
        } else {
            $lineTotal = bcadd($lineSubtotal, $taxAmount, 4);
        }

        return [
            'unit_price' => $unitPrice,
            'line_subtotal' => $lineSubtotal,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    public function calculateTax(string $lineSubtotal, ?Tax $tax): string
    {
        if (! $tax || (float) $tax->rate <= 0) {
            return '0.0000';
        }

        $rate = bcdiv((string) $tax->rate, '100', 6);

        if ($tax->is_inclusive) {
            return bcdiv(bcmul($lineSubtotal, $rate, 6), bcadd('1', $rate, 6), 4);
        }

        return bcmul($lineSubtotal, $rate, 4);
    }

    /** @param  list<array{line_subtotal: string, tax_amount: string, line_total: string, tax_is_inclusive?: bool}>  $lines */
    public function summarizeCart(array $lines): array
    {
        $subtotal = '0.0000';
        $taxTotal = '0.0000';
        $grandTotal = '0.0000';

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, $line['line_subtotal'], 4);
            $taxTotal = bcadd($taxTotal, $line['tax_amount'], 4);
            $grandTotal = bcadd($grandTotal, $line['line_total'], 4);
        }

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => '0.0000',
            'grand_total' => $grandTotal,
        ];
    }
}
