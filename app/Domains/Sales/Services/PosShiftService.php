<?php

namespace App\Domains\Sales\Services;

use App\Models\PosShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosShiftService
{
    public function __construct(
        protected PosContextService $context,
        protected AuditLogger $auditLogger,
    ) {}

    public function open(User $user, Store $store, Register $register, float $openingFloat = 0): PosShift
    {
        $existing = PosShift::query()
            ->where('register_id', $register->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            if ($existing->user_id === $user->id) {
                $this->context->setShift($existing);

                return $existing;
            }

            throw ValidationException::withMessages(['register_id' => 'This register already has an open shift.']);
        }

        $shift = PosShift::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opening_float' => $openingFloat,
            'opened_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->context->setShift($shift);

        $this->auditLogger->log('open', 'pos_shifts', PosShift::class, $shift->id, null, $shift->toArray(), $user);

        return $shift;
    }

    public function close(User $user, PosShift $shift, float $closingFloat): PosShift
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages(['shift' => 'Shift is already closed.']);
        }

        if ($shift->user_id !== $user->id && ! $user->hasRole(['Store Manager', 'Supervisor', 'Company Admin', 'Super Admin', 'Developer'])) {
            throw ValidationException::withMessages(['shift' => 'You cannot close this shift.']);
        }

        $expectedCash = $this->expectedCashForShift($shift);

        $shift->update([
            'status' => 'closed',
            'closing_float' => $closingFloat,
            'expected_cash' => $expectedCash,
            'closed_at' => now(),
            'updated_by' => $user->id,
        ]);

        $this->context->clearShift();

        $this->auditLogger->log('close', 'pos_shifts', PosShift::class, $shift->id, null, $shift->fresh()->toArray(), $user);

        return $shift->fresh();
    }

    public function expectedCashForShift(PosShift $shift): string
    {
        $cashSales = Sale::query()
            ->where('pos_shift_id', $shift->id)
            ->where('status', 'completed')
            ->join('sale_payments', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sale_payments.payment_method', 'cash')
            ->sum('sale_payments.amount');

        $crossShiftRefunds = SaleRefund::query()
            ->where('pos_shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->whereHas('sale', fn ($query) => $query->where('pos_shift_id', '!=', $shift->id))
            ->sum('amount');

        $withSales = bcadd((string) $shift->opening_float, (string) $cashSales, 4);

        return bcsub($withSales, (string) $crossShiftRefunds, 4);
    }

    public function getOpenShiftForRegister(string $registerId): ?PosShift
    {
        return PosShift::query()
            ->where('register_id', $registerId)
            ->where('status', 'open')
            ->first();
    }
}
