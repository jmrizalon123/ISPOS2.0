<?php

namespace App\Domains\Accounting\Services;

use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Collection;

class ApReportService
{
    /** @return Collection<int, object> */
    public function aging(User $user, ?string $companyId = null, ?string $asOfDate = null): Collection
    {
        $asOf = $asOfDate ?: now()->toDateString();

        $query = VendorBill::query()
            ->with('supplier:id,name')
            ->whereIn('status', ['open', 'partial']);

        if ($user->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        $bills = $query->orderBy('supplier_id')->orderBy('bill_date')->get();

        return $bills->map(function (VendorBill $bill) use ($asOf) {
            $balance = $bill->balanceDue();
            $dueDate = $bill->due_date?->toDateString() ?? $bill->bill_date->toDateString();
            $daysPastDue = max(0, (int) floor((strtotime($asOf) - strtotime($dueDate)) / 86400));

            $bucket = match (true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => '1_30',
                $daysPastDue <= 60 => '31_60',
                $daysPastDue <= 90 => '61_90',
                default => 'over_90',
            };

            return (object) [
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'supplier_id' => $bill->supplier_id,
                'supplier_name' => $bill->supplier?->name,
                'bill_date' => $bill->bill_date->toDateString(),
                'due_date' => $dueDate,
                'days_past_due' => $daysPastDue,
                'bucket' => $bucket,
                'amount_due' => (string) $bill->amount_due,
                'amount_paid' => (string) $bill->amount_paid,
                'balance_due' => $balance,
            ];
        })->filter(fn ($row) => bccomp($row->balance_due, '0', 4) > 0)->values();
    }

    /** @return array{current: string, bucket_1_30: string, bucket_31_60: string, bucket_61_90: string, over_90: string, total: string} */
    public function agingTotals(Collection $rows): array
    {
        $totals = [
            'current' => '0',
            'bucket_1_30' => '0',
            'bucket_31_60' => '0',
            'bucket_61_90' => '0',
            'over_90' => '0',
            'total' => '0',
        ];

        foreach ($rows as $row) {
            $key = match ($row->bucket) {
                'current' => 'current',
                '1_30' => 'bucket_1_30',
                '31_60' => 'bucket_31_60',
                '61_90' => 'bucket_61_90',
                default => 'over_90',
            };

            $totals[$key] = bcadd($totals[$key], $row->balance_due, 4);
            $totals['total'] = bcadd($totals['total'], $row->balance_due, 4);
        }

        return $totals;
    }
}
