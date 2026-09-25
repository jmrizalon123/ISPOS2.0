<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Accounting\Services\GlPostingService;
use App\Domains\Inventory\Services\StockMovementService;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    public function __construct(
        protected StockMovementService $stockMovement,
        protected GlPostingService $glPostingService,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(User $user, Store $store, Supplier $supplier, array $header, array $lines): PurchaseReturn
    {
        if ($store->company_id !== $supplier->company_id) {
            throw ValidationException::withMessages(['supplier_id' => 'Supplier does not belong to the store company.']);
        }

        return DB::transaction(function () use ($user, $store, $supplier, $header, $lines) {
            $return = PurchaseReturn::create([
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $header['purchase_order_id'] ?? null,
                'return_number' => $this->nextReturnNumber($store),
                'status' => 'draft',
                'reason' => $header['reason'],
                'notes' => $header['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncLines($return, $lines);
            $this->auditLogger->log('create', 'purchasing', PurchaseReturn::class, $return->id, null, [
                'return_number' => $return->return_number,
            ], $user);

            return $return->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function updateDraft(PurchaseReturn $return, User $user, array $header, array $lines): PurchaseReturn
    {
        if (! $return->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft returns can be edited.']);
        }

        return DB::transaction(function () use ($return, $user, $header, $lines) {
            $return->update([
                'reason' => $header['reason'],
                'notes' => $header['notes'] ?? null,
                'purchase_order_id' => $header['purchase_order_id'] ?? null,
                'updated_by' => $user->id,
            ]);

            $return->lines()->delete();
            $this->syncLines($return, $lines);

            return $return->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function post(PurchaseReturn $return, User $user): PurchaseReturn
    {
        if (! $return->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft returns can be posted.']);
        }

        if ($return->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Return must have at least one line.']);
        }

        return DB::transaction(function () use ($return, $user) {
            $return->load(['lines.product', 'store']);

            foreach ($return->lines as $line) {
                $qty = (string) $line->qty;

                $this->stockMovement->record(
                    $return->company_id,
                    $return->store_id,
                    $line->product_id,
                    'purchase_return',
                    bcmul($qty, '-1', 4),
                    $user,
                    [
                        'reference_type' => PurchaseReturnLine::class,
                        'reference_id' => $line->id,
                        'reason' => $return->reason,
                    ],
                );
            }

            $return->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->glPostingService->reversePurchaseReturn($return, $user);

            $this->auditLogger->log('post', 'purchasing', PurchaseReturn::class, $return->id, ['status' => 'draft'], ['status' => 'posted'], $user);

            return $return->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function delete(PurchaseReturn $return, User $user): void
    {
        if (! $return->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft returns can be deleted.']);
        }

        $this->auditLogger->log('delete', 'purchasing', PurchaseReturn::class, $return->id, $return->toArray(), null, $user);
        $return->delete();
    }

    /** @param  list<array{product_id: string, qty: float|string, unit_cost: float|string, purchase_order_line_id?: string|null}>  $lines */
    protected function syncLines(PurchaseReturn $return, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $product = Product::query()->findOrFail($line['product_id']);

            if ($product->company_id !== $return->company_id) {
                throw ValidationException::withMessages(["lines.{$index}.product_id" => 'Product does not belong to the company.']);
            }

            if (! $product->track_inventory) {
                throw ValidationException::withMessages(["lines.{$index}.product_id" => 'Product must track inventory.']);
            }

            $qty = (string) $line['qty'];
            $unitCost = (string) $line['unit_cost'];

            PurchaseReturnLine::create([
                'purchase_return_id' => $return->id,
                'product_id' => $product->id,
                'purchase_order_line_id' => $line['purchase_order_line_id'] ?? null,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'line_total' => bcmul($qty, $unitCost, 4),
            ]);
        }
    }

    protected function nextReturnNumber(Store $store): string
    {
        $count = PurchaseReturn::query()
            ->where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-PR-%s-%s', $store->store_code, now()->format('Ymd'), $seq);
    }
}
