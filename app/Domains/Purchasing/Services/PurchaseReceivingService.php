<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Accounting\Services\GlPostingService;
use App\Domains\Accounting\Services\VendorBillService;
use App\Domains\Inventory\Services\StockMovementService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceivingService
{
    public function __construct(
        protected StockMovementService $stockMovement,
        protected VendorBillService $vendorBillService,
        protected GlPostingService $glPostingService,
        protected AuditLogger $auditLogger,
    ) {}

    /** @param  list<array{line_id: string, receive_qty: float|string}>  $receipts */
    public function receive(PurchaseOrder $po, User $user, array $receipts): PurchaseOrder
    {
        if (! $po->isReceivable()) {
            throw ValidationException::withMessages(['status' => 'Purchase order is not receivable.']);
        }

        return DB::transaction(function () use ($po, $user, $receipts) {
            $po->load(['lines.product', 'store', 'supplier']);

            $receiptLines = [];
            $totalAmount = '0';

            foreach ($receipts as $index => $receipt) {
                $receiveQty = (string) $receipt['receive_qty'];

                if (bccomp($receiveQty, '0', 4) <= 0) {
                    continue;
                }

                /** @var PurchaseOrderLine|null $line */
                $line = $po->lines->firstWhere('id', $receipt['line_id']);
                if (! $line) {
                    throw ValidationException::withMessages(["receipts.{$index}.line_id" => 'Invalid line.']);
                }

                $remaining = $line->remainingQty();
                if (bccomp($receiveQty, $remaining, 4) > 0) {
                    throw ValidationException::withMessages(["receipts.{$index}.receive_qty" => "Cannot receive more than remaining qty ({$remaining})."]);
                }

                $lineTotal = bcmul($receiveQty, (string) $line->unit_cost, 4);
                $totalAmount = bcadd($totalAmount, $lineTotal, 4);

                $line->update([
                    'received_qty' => bcadd((string) $line->received_qty, $receiveQty, 4),
                ]);

                $this->stockMovement->record(
                    $po->company_id,
                    $po->store_id,
                    $line->product_id,
                    'purchase_receipt',
                    $receiveQty,
                    $user,
                    [
                        'reference_type' => PurchaseOrderLine::class,
                        'reference_id' => $line->id,
                        'reason' => "PO {$po->po_number}",
                    ],
                );

                $receiptLines[] = [
                    'purchase_order_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'qty' => $receiveQty,
                    'unit_cost' => (string) $line->unit_cost,
                    'line_total' => $lineTotal,
                ];
            }

            if ($receiptLines !== []) {
                $purchaseReceipt = PurchaseReceipt::create([
                    'company_id' => $po->company_id,
                    'store_id' => $po->store_id,
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_id' => $po->id,
                    'receipt_number' => $this->nextReceiptNumber($po->store),
                    'receipt_date' => now()->toDateString(),
                    'total_amount' => $totalAmount,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                foreach ($receiptLines as $lineData) {
                    PurchaseReceiptLine::create([
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        ...$lineData,
                    ]);
                }

                $vendorBill = $this->vendorBillService->createFromReceipt($purchaseReceipt, $user);
                $this->glPostingService->postPurchaseReceipt($purchaseReceipt, $user);

                $this->auditLogger->log('create', 'purchasing', PurchaseReceipt::class, $purchaseReceipt->id, null, [
                    'receipt_number' => $purchaseReceipt->receipt_number,
                    'bill_number' => $vendorBill->bill_number,
                    'total_amount' => $totalAmount,
                ], $user);
            }

            $po->refresh()->load('lines');
            $allReceived = $po->lines->every(fn (PurchaseOrderLine $line) => bccomp($line->remainingQty(), '0', 4) <= 0);
            $anyReceived = $po->lines->contains(fn (PurchaseOrderLine $line) => bccomp((string) $line->received_qty, '0', 4) > 0);

            $status = 'approved';
            if ($allReceived) {
                $status = 'received';
            } elseif ($anyReceived) {
                $status = 'partially_received';
            }

            $po->update([
                'status' => $status,
                'received_at' => $allReceived ? now() : $po->received_at,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->log('receive', 'purchasing', PurchaseOrder::class, $po->id, null, [
                'status' => $status,
                'receipts' => $receipts,
            ], $user);

            return $po->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    protected function nextReceiptNumber(Store $store): string
    {
        $count = PurchaseReceipt::query()
            ->where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-RCV-%s-%s', $store->store_code, now()->format('Ymd'), $seq);
    }
}
