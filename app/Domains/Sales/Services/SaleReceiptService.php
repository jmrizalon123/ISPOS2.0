<?php

namespace App\Domains\Sales\Services;

use App\Models\Sale;
use App\Services\SettingService;

class SaleReceiptService
{
    public function __construct(protected SettingService $settings) {}

    /**
     * @return array{
     *     sale: array<string, mixed>,
     *     store: array<string, mixed>,
     *     company: array<string, mixed>,
     *     branding: array{header: string|null, footer: string|null},
     *     lines: list<array<string, mixed>>,
     *     payments: list<array{payment_method: string, amount: string, reference: string|null}>,
     *     cashier: string|null,
     *     register: string|null,
     *     customer: string|null
     * }
     */
    public function build(Sale $sale): array
    {
        $sale->loadMissing([
            'company',
            'store',
            'register',
            'user',
            'customer',
            'lines.modifiers',
            'payments',
        ]);

        $store = $sale->store;
        $company = $sale->company;

        $header = $store?->receipt_header
            ?: $company?->receipt_header
            ?: null;

        $footer = $store?->receipt_footer
            ?: $company?->receipt_footer
            ?: (string) $this->settings->get(
                'company.receipt_footer',
                'Thank you for your purchase!',
                $sale->company_id,
                $sale->store_id,
                $sale->register_id,
            );

        $addressParts = array_filter([
            $store?->address_line_1,
            $store?->address_line_2,
            $store?->barangay,
            $store?->city,
            $store?->province,
            $store?->postal_code,
        ]);

        return [
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'status' => $sale->status,
                'completed_at' => $sale->completed_at?->toIso8601String(),
                'subtotal' => (string) $sale->subtotal,
                'tax_total' => (string) $sale->tax_total,
                'discount_total' => (string) $sale->discount_total,
                'grand_total' => (string) $sale->grand_total,
                'refund_reason' => $sale->refund_reason,
            ],
            'store' => [
                'name' => $store?->store_name,
                'code' => $store?->store_code,
                'phone' => $store?->phone ?: $store?->mobile,
                'tin' => $store?->tin,
                'address' => implode(', ', $addressParts),
            ],
            'company' => [
                'name' => $company?->display_name ?: $company?->name,
                'tin' => $company?->tin ?? null,
            ],
            'branding' => [
                'header' => $header ? trim((string) $header) : null,
                'footer' => $footer ? trim((string) $footer) : null,
            ],
            'lines' => $sale->lines->map(function ($line) {
                return [
                    'name' => $line->name,
                    'sku' => $line->sku,
                    'qty' => (string) $line->qty,
                    'unit_price' => (string) $line->unit_price,
                    'line_total' => (string) $line->line_total,
                    'modifiers' => $line->modifiers->map(fn ($modifier) => [
                        'name' => $modifier->option_name,
                        'price_adjustment' => (string) $modifier->price_adjustment,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'payments' => $sale->payments->map(fn ($payment) => [
                'payment_method' => $payment->payment_method,
                'amount' => (string) $payment->amount,
                'reference' => $payment->reference,
            ])->values()->all(),
            'cashier' => $sale->user?->name,
            'register' => $sale->register?->register_name ?: $sale->register?->register_code,
            'customer' => $sale->customer?->displayName(),
        ];
    }

    /** Plain-text receipt for Windows silent PrintDocument / thermal printers. */
    public function buildPlainText(Sale $sale, ?string $change = null): string
    {
        $receipt = $this->build($sale);
        $money = static fn (string|float|null $value): string => 'PHP '.number_format((float) $value, 2);
        $lines = [];

        $title = $receipt['store']['name'] ?: ($receipt['company']['name'] ?? 'iSPOS');
        $lines[] = $title;
        if ($receipt['branding']['header']) {
            foreach (preg_split("/\r\n|\n|\r/", $receipt['branding']['header']) ?: [] as $headerLine) {
                $lines[] = $headerLine;
            }
        }
        if ($receipt['store']['address']) {
            $lines[] = $receipt['store']['address'];
        }
        if ($receipt['store']['phone']) {
            $lines[] = 'Tel: '.$receipt['store']['phone'];
        }
        if ($receipt['store']['tin'] || $receipt['company']['tin']) {
            $lines[] = 'TIN: '.($receipt['store']['tin'] ?: $receipt['company']['tin']);
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = 'Sale #: '.$receipt['sale']['sale_number'];
        if (! empty($receipt['sale']['completed_at'])) {
            $lines[] = 'Date: '.\Illuminate\Support\Carbon::parse($receipt['sale']['completed_at'])
                ->timezone(config('app.timezone'))
                ->format('Y-m-d H:i');
        }
        if ($receipt['cashier']) {
            $lines[] = 'Cashier: '.$receipt['cashier'];
        }
        if ($receipt['register']) {
            $lines[] = 'Register: '.$receipt['register'];
        }
        if ($receipt['customer']) {
            $lines[] = 'Customer: '.$receipt['customer'];
        }

        if (in_array($receipt['sale']['status'], ['voided', 'refunded'], true)) {
            $lines[] = strtoupper($receipt['sale']['status']);
        }

        $lines[] = str_repeat('-', 32);

        foreach ($receipt['lines'] as $line) {
            $lines[] = $line['name'];
            $lines[] = sprintf(
                '  %s x %s',
                rtrim(rtrim(number_format((float) $line['qty'], 3, '.', ''), '0'), '.'),
                $money($line['unit_price']),
            );
            $lines[] = '  '.$money($line['line_total']);
            foreach ($line['modifiers'] as $modifier) {
                $adj = (float) $modifier['price_adjustment'] !== 0.0
                    ? ' '.$money($modifier['price_adjustment'])
                    : '';
                $lines[] = '  + '.$modifier['name'].$adj;
            }
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = 'Subtotal  '.$money($receipt['sale']['subtotal']);
        if ((float) $receipt['sale']['discount_total'] > 0) {
            $lines[] = 'Discount  -'.$money($receipt['sale']['discount_total']);
        }
        $lines[] = 'Tax       '.$money($receipt['sale']['tax_total']);
        $lines[] = 'TOTAL     '.$money($receipt['sale']['grand_total']);
        $lines[] = str_repeat('-', 32);

        foreach ($receipt['payments'] as $payment) {
            $ref = $payment['reference'] ? ' ('.$payment['reference'].')' : '';
            $lines[] = ucfirst($payment['payment_method']).$ref.'  '.$money($payment['amount']);
        }

        if ($change !== null && (float) $change > 0) {
            $lines[] = 'Change    '.$money($change);
        }

        if (! empty($receipt['sale']['refund_reason'])) {
            $lines[] = 'Refund: '.$receipt['sale']['refund_reason'];
        }

        $lines[] = str_repeat('-', 32);
        $footer = $receipt['branding']['footer'] ?: 'Thank you for your purchase!';
        foreach (preg_split("/\r\n|\n|\r/", $footer) ?: [] as $footerLine) {
            $lines[] = $footerLine;
        }
        $lines[] = '';

        return implode("\n", $lines);
    }
}
