<?php

namespace App\Domains\Accounting\Services;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected GlPostingService $glPostingService,
    ) {
    }

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = SupplierPayment::query()->with(['supplier:id,name', 'allocations.vendorBill:id,bill_number']);

        if ($user->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('payment_date')->orderByDesc('payment_number')->paginate($perPage)->withQueryString();
    }

    /** @param  list<array{vendor_bill_id: string, amount: float|string}>  $allocations */
    public function create(User $user, Supplier $supplier, array $header, array $allocations): SupplierPayment
    {
        if ($supplier->company_id !== $header['company_id']) {
            throw ValidationException::withMessages(['supplier_id' => 'Supplier does not belong to the company.']);
        }

        $amount = (string) $header['amount'];
        $allocatedTotal = '0';

        foreach ($allocations as $index => $allocation) {
            $allocatedTotal = bcadd($allocatedTotal, (string) $allocation['amount'], 4);
        }

        if (bccomp($allocatedTotal, $amount, 4) !== 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must equal the sum of bill allocations.']);
        }

        if (bccomp($amount, '0', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($user, $supplier, $header, $allocations, $amount) {
            $payment = SupplierPayment::create([
                'company_id' => $header['company_id'],
                'supplier_id' => $supplier->id,
                'payment_number' => $this->nextPaymentNumber($header['company_id']),
                'payment_date' => $header['payment_date'],
                'payment_method' => $header['payment_method'] ?? 'cash',
                'amount' => $amount,
                'reference' => $header['reference'] ?? null,
                'notes' => $header['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($allocations as $index => $allocation) {
                $allocAmount = (string) $allocation['amount'];

                /** @var VendorBill|null $bill */
                $bill = VendorBill::query()
                    ->where('company_id', $payment->company_id)
                    ->where('supplier_id', $supplier->id)
                    ->find($allocation['vendor_bill_id']);

                if (! $bill) {
                    throw ValidationException::withMessages(["allocations.{$index}.vendor_bill_id" => 'Invalid vendor bill.']);
                }

                if (! $bill->isOpen()) {
                    throw ValidationException::withMessages(["allocations.{$index}.vendor_bill_id" => 'Bill is not open for payment.']);
                }

                $balance = $bill->balanceDue();
                if (bccomp($allocAmount, $balance, 4) > 0) {
                    throw ValidationException::withMessages(["allocations.{$index}.amount" => "Allocation exceeds bill balance ({$balance})."]);
                }

                SupplierPaymentAllocation::create([
                    'supplier_payment_id' => $payment->id,
                    'vendor_bill_id' => $bill->id,
                    'amount' => $allocAmount,
                ]);

                $newPaid = bcadd((string) $bill->amount_paid, $allocAmount, 4);
                $status = bccomp($newPaid, (string) $bill->amount_due, 4) >= 0 ? 'paid' : 'partial';

                $bill->update([
                    'amount_paid' => $newPaid,
                    'status' => $status,
                    'updated_by' => $user->id,
                ]);
            }

            $this->glPostingService->postSupplierPayment($payment, $user);

            $this->auditLogger->log('create', 'accounts_payable', SupplierPayment::class, $payment->id, null, [
                'payment_number' => $payment->payment_number,
                'amount' => $amount,
            ], $user);

            return $payment->fresh(['supplier', 'allocations.vendorBill']);
        });
    }

    /** @return \Illuminate\Support\Collection<int, VendorBill> */
    public function openBillsForSupplier(User $user, string $supplierId, ?string $companyId = null)
    {
        $query = VendorBill::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('bill_date');

        if ($user->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        return $query->get(['id', 'bill_number', 'bill_date', 'due_date', 'amount_due', 'amount_paid', 'status']);
    }

    protected function nextPaymentNumber(string $companyId): string
    {
        $count = SupplierPayment::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        return sprintf('PAY-%s-%04d', now()->format('Ymd'), $count + 1);
    }
}
