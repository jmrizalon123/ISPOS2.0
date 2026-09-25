<?php

namespace App\Domains\Accounting\Services;

use App\Models\PurchaseReceipt;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorBillService
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = VendorBill::query()->with(['supplier:id,name', 'purchaseOrder:id,po_number']);

        if ($user->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('bill_date')->orderByDesc('bill_number')->paginate($perPage)->withQueryString();
    }

    public function createFromReceipt(PurchaseReceipt $receipt, User $user): VendorBill
    {
        return DB::transaction(function () use ($receipt, $user) {
            $bill = VendorBill::create([
                'company_id' => $receipt->company_id,
                'supplier_id' => $receipt->supplier_id,
                'purchase_receipt_id' => $receipt->id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'bill_number' => $this->nextBillNumber($receipt->company_id),
                'bill_date' => $receipt->receipt_date,
                'due_date' => $receipt->receipt_date->copy()->addDays(30),
                'status' => 'open',
                'amount_due' => $receipt->total_amount,
                'amount_paid' => 0,
                'notes' => "From receipt {$receipt->receipt_number}",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->log('create', 'accounts_payable', VendorBill::class, $bill->id, null, [
                'bill_number' => $bill->bill_number,
                'receipt_number' => $receipt->receipt_number,
            ], $user);

            return $bill->fresh(['supplier', 'purchaseOrder', 'purchaseReceipt']);
        });
    }

    protected function nextBillNumber(string $companyId): string
    {
        $count = VendorBill::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        return sprintf('BILL-%s-%04d', now()->format('Ymd'), $count + 1);
    }
}
