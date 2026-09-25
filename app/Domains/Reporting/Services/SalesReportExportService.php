<?php

namespace App\Domains\Reporting\Services;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportExportService
{
    public function __construct(protected SalesRegisterReportService $registerReportService) {}

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     */
    public function registerCsv(User $user, array $filters): StreamedResponse
    {
        $filename = 'sales-register-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($user, $filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Sale Number', 'Date', 'Status', 'Store', 'Register', 'Cashier',
                'Customer', 'Subtotal', 'Discount', 'Tax', 'Grand Total',
            ]);

            $this->registerReportService
                ->baseQuery($user, $filters)
                ->chunk(100, function ($sales) use ($handle) {
                    foreach ($sales as $sale) {
                        fputcsv($handle, [
                            $sale->sale_number,
                            $sale->completed_at?->toDateTimeString(),
                            $sale->status,
                            $sale->store?->store_name,
                            $sale->register?->register_name,
                            $sale->user?->name,
                            $sale->customer
                                ? trim($sale->customer->first_name.' '.($sale->customer->last_name ?? ''))
                                : '',
                            $sale->subtotal,
                            $sale->discount_total,
                            $sale->tax_total,
                            $sale->grand_total,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
