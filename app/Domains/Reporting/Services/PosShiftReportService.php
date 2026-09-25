<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Reporting\Services\Concerns\ScopesShiftReports;
use App\Domains\Sales\Services\PosShiftService;
use App\Models\PosShift;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PosShiftReportService
{
    use ScopesShiftReports;

    public function __construct(protected PosShiftService $shiftService) {}

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     */
    public function paginate(User $user, array $filters, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->applyShiftFilters($this->scopedShiftsQuery($user), $user, $filters)
            ->with(['store:id,store_name,store_code', 'register:id,register_name,register_code', 'user:id,name'])
            ->orderByDesc('opened_at');

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->whereHas('store', fn ($store) => $store->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%"))
                    ->orWhereHas('register', fn ($register) => $register->where('register_name', 'like', "%{$search}%")
                        ->orWhere('register_code', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($cashier) => $cashier->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->through(fn (PosShift $shift) => $this->serializeListRow($shift));
    }

    /** @return array<string, mixed> */
    public function detail(User $user, PosShift $shift): array
    {
        abort_unless($this->canViewShift($user, $shift), 403);

        $shift->load(['store:id,store_name,store_code', 'register:id,register_name,register_code', 'user:id,name,email']);

        $salesStats = $this->salesStatsForShift($shift);
        $expectedCash = $shift->isOpen()
            ? $this->shiftService->expectedCashForShift($shift)
            : (string) ($shift->expected_cash ?? $this->shiftService->expectedCashForShift($shift));

        $closingFloat = $shift->closing_float !== null ? (string) $shift->closing_float : null;
        $variance = ($closingFloat !== null && $expectedCash !== null)
            ? bcsub($closingFloat, $expectedCash, 4)
            : null;

        return [
            'shift' => [
                'id' => $shift->id,
                'status' => $shift->status,
                'opened_at' => $shift->opened_at?->toIso8601String(),
                'closed_at' => $shift->closed_at?->toIso8601String(),
                'opening_float' => (string) $shift->opening_float,
                'expected_cash' => $expectedCash,
                'closing_float' => $closingFloat,
                'variance' => $variance,
                'store' => [
                    'id' => $shift->store->id,
                    'store_name' => $shift->store->store_name,
                    'store_code' => $shift->store->store_code,
                ],
                'register' => [
                    'id' => $shift->register->id,
                    'name' => $shift->register->register_name,
                    'code' => $shift->register->register_code,
                ],
                'cashier' => [
                    'id' => $shift->user->id,
                    'name' => $shift->user->name,
                ],
            ],
            'summary' => $salesStats,
            'payments' => $this->paymentBreakdown($shift),
            'sales' => Sale::query()
                ->where('pos_shift_id', $shift->id)
                ->with(['customer:id,first_name,last_name'])
                ->orderBy('completed_at')
                ->get(['id', 'sale_number', 'status', 'grand_total', 'completed_at', 'voided_at', 'customer_id'])
                ->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'status' => $sale->status,
                    'grand_total' => (string) $sale->grand_total,
                    'completed_at' => $sale->completed_at?->toIso8601String(),
                    'voided_at' => $sale->voided_at?->toIso8601String(),
                    'customer_name' => $sale->customer?->displayName(),
                ])
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeListRow(PosShift $shift): array
    {
        $stats = $this->salesStatsForShift($shift);
        $expectedCash = $shift->isOpen()
            ? $this->shiftService->expectedCashForShift($shift)
            : (string) ($shift->expected_cash ?? $this->shiftService->expectedCashForShift($shift));

        $closingFloat = $shift->closing_float !== null ? (string) $shift->closing_float : null;
        $variance = ($closingFloat !== null && $expectedCash !== null)
            ? bcsub($closingFloat, $expectedCash, 4)
            : null;

        return [
            'id' => $shift->id,
            'status' => $shift->status,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'opening_float' => (string) $shift->opening_float,
            'expected_cash' => $expectedCash,
            'closing_float' => $closingFloat,
            'variance' => $variance,
            'store_name' => $shift->store?->store_name,
            'store_code' => $shift->store?->store_code,
            'register_name' => $shift->register?->register_name,
            'register_code' => $shift->register?->register_code,
            'cashier_name' => $shift->user?->name,
            'transaction_count' => $stats['transaction_count'],
            'gross_sales' => $stats['gross_sales'],
            'void_count' => $stats['void_count'],
        ];
    }

    /** @return array{transaction_count: int, gross_sales: string, void_count: int, void_total: string, net_sales: string} */
    protected function salesStatsForShift(PosShift $shift): array
    {
        $base = Sale::query()->where('pos_shift_id', $shift->id);

        $grossSales = (string) ((clone $base)->where('status', 'completed')->sum('grand_total') ?: '0.0000');
        $voidTotal = (string) ((clone $base)->where('status', 'voided')->sum('grand_total') ?: '0.0000');

        return [
            'transaction_count' => (int) (clone $base)->where('status', 'completed')->count(),
            'gross_sales' => $grossSales,
            'void_count' => (int) (clone $base)->where('status', 'voided')->count(),
            'void_total' => $voidTotal,
            'net_sales' => bcsub($grossSales, $voidTotal, 4),
        ];
    }

    /** @return list<array{payment_method: string, amount: string, count: int}> */
    protected function paymentBreakdown(PosShift $shift): array
    {
        return Sale::query()
            ->where('pos_shift_id', $shift->id)
            ->where('status', 'completed')
            ->join('sale_payments', 'sales.id', '=', 'sale_payments.sale_id')
            ->select([
                'sale_payments.payment_method',
                DB::raw('SUM(sale_payments.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT sales.id) as sale_count'),
            ])
            ->groupBy('sale_payments.payment_method')
            ->orderBy('sale_payments.payment_method')
            ->get()
            ->map(fn ($row) => [
                'payment_method' => $row->payment_method,
                'amount' => (string) $row->total_amount,
                'count' => (int) $row->sale_count,
            ])
            ->values()
            ->all();
    }
}
