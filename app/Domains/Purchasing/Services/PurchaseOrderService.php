<?php

namespace App\Domains\Purchasing\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(User $user, Store $store, Supplier $supplier, array $header, array $lines): PurchaseOrder
    {
        $this->assertSameCompany($store, $supplier);

        return DB::transaction(function () use ($user, $store, $supplier, $header, $lines) {
            $po = PurchaseOrder::create([
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'po_number' => $this->nextPoNumber($store),
                'status' => 'draft',
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'notes' => $header['notes'] ?? null,
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncLines($po, $lines, $user);
            $this->recalculateTotals($po);

            $this->auditLogger->log('create', 'purchasing', PurchaseOrder::class, $po->id, null, [
                'po_number' => $po->po_number,
                'status' => $po->status,
            ], $user);

            return $po->fresh(['lines.product']);
        });
    }

    public function updateDraft(PurchaseOrder $po, User $user, array $header, array $lines): PurchaseOrder
    {
        if (! $po->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be edited.']);
        }

        return DB::transaction(function () use ($po, $user, $header, $lines) {
            $old = $po->toArray();

            $po->update([
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'notes' => $header['notes'] ?? null,
                'updated_by' => $user->id,
            ]);

            if (isset($header['store_id']) && $header['store_id'] !== $po->store_id) {
                $store = Store::query()->findOrFail($header['store_id']);
                abort_unless($store->company_id === $po->company_id, 422);
                $po->update(['store_id' => $store->id]);
            }

            if (isset($header['supplier_id']) && $header['supplier_id'] !== $po->supplier_id) {
                $supplier = Supplier::query()->findOrFail($header['supplier_id']);
                abort_unless($supplier->company_id === $po->company_id, 422);
                $po->update(['supplier_id' => $supplier->id]);
            }

            $po->lines()->delete();
            $this->syncLines($po, $lines, $user);
            $this->recalculateTotals($po);

            $this->auditLogger->log('update', 'purchasing', PurchaseOrder::class, $po->id, $old, $po->fresh()->toArray(), $user);

            return $po->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function approve(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if (! $po->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be approved.']);
        }

        if ($po->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Purchase order must have at least one line.']);
        }

        $po->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->auditLogger->log('approve', 'purchasing', PurchaseOrder::class, $po->id, ['status' => 'draft'], ['status' => 'approved'], $user);

        return $po->fresh(['lines.product', 'supplier', 'store']);
    }

    public function cancel(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if (! in_array($po->status, ['approved', 'partially_received'], true)) {
            throw ValidationException::withMessages(['status' => 'Only approved or partially received purchase orders can be cancelled.']);
        }

        $po->update([
            'status' => 'cancelled',
            'updated_by' => $user->id,
        ]);

        $this->auditLogger->log('cancel', 'purchasing', PurchaseOrder::class, $po->id, null, ['status' => 'cancelled'], $user);

        return $po->fresh();
    }

    public function delete(PurchaseOrder $po, User $user): void
    {
        if (! $po->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be deleted.']);
        }

        $this->auditLogger->log('delete', 'purchasing', PurchaseOrder::class, $po->id, $po->toArray(), null, $user);
        $po->delete();
    }

    /** @param  list<array{product_id: string, ordered_qty: float|string, unit_cost: float|string, notes?: string|null}>  $lines */
    protected function syncLines(PurchaseOrder $po, array $lines, User $user): void
    {
        foreach ($lines as $index => $line) {
            $product = Product::query()->findOrFail($line['product_id']);

            if ($product->company_id !== $po->company_id) {
                throw ValidationException::withMessages(["lines.{$index}.product_id" => 'Product does not belong to the company.']);
            }

            if (! $product->track_inventory) {
                throw ValidationException::withMessages(["lines.{$index}.product_id" => 'Product must track inventory.']);
            }

            $orderedQty = (string) $line['ordered_qty'];
            $unitCost = (string) $line['unit_cost'];
            $lineTotal = bcmul($orderedQty, $unitCost, 4);

            PurchaseOrderLine::create([
                'purchase_order_id' => $po->id,
                'product_id' => $product->id,
                'line_number' => $index + 1,
                'ordered_qty' => $orderedQty,
                'received_qty' => 0,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    protected function recalculateTotals(PurchaseOrder $po): void
    {
        $po->load('lines');
        $subtotal = '0.0000';

        foreach ($po->lines as $line) {
            $subtotal = bcadd($subtotal, (string) $line->line_total, 4);
        }

        $po->update([
            'subtotal' => $subtotal,
            'tax_total' => '0.0000',
            'grand_total' => $subtotal,
        ]);
    }

    protected function nextPoNumber(Store $store): string
    {
        $count = PurchaseOrder::query()
            ->where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-PO-%s-%s', $store->store_code, now()->format('Ymd'), $seq);
    }

    protected function assertSameCompany(Store $store, Supplier $supplier): void
    {
        if ($store->company_id !== $supplier->company_id) {
            throw ValidationException::withMessages(['supplier_id' => 'Supplier does not belong to the store company.']);
        }
    }
}
