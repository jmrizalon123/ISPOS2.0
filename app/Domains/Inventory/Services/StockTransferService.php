<?php

namespace App\Domains\Inventory\Services;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        protected StockMovementService $stockMovement,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<array{product_id: string, quantity?: float|string, requested_quantity?: float|string, product_variant_id?: string|null, notes?: string|null, batch_no?: string|null, serial_no?: string|null, expiry_date?: string|null}>  $lines
     * @param  array<string, mixed>  $meta
     */
    public function createAndComplete(
        User $user,
        Store $fromStore,
        Store $toStore,
        array $lines,
        ?string $notes = null,
        array $meta = [],
    ): StockTransfer {
        if ($fromStore->id === $toStore->id) {
            throw ValidationException::withMessages(['to_store_id' => 'Destination must be different from source.']);
        }

        if ($fromStore->company_id !== $toStore->company_id) {
            throw ValidationException::withMessages(['to_store_id' => 'Locations must belong to the same company.']);
        }

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'Add at least one product line.']);
        }

        $transferType = $meta['transfer_type'] ?? 'store_to_store';
        if (! in_array($transferType, StockTransfer::TYPES, true)) {
            throw ValidationException::withMessages(['transfer_type' => 'Invalid transfer type.']);
        }

        $priority = $meta['priority'] ?? 'normal';
        if (! in_array($priority, StockTransfer::PRIORITIES, true)) {
            throw ValidationException::withMessages(['priority' => 'Invalid priority.']);
        }

        return DB::transaction(function () use ($user, $fromStore, $toStore, $lines, $notes, $meta, $transferType, $priority) {
            $now = now();
            $transferDate = $meta['transfer_date'] ?? $now->toDateString();
            $number = $this->nextTransferNumber($fromStore);

            $transfer = StockTransfer::create([
                'company_id' => $fromStore->company_id,
                'from_store_id' => $fromStore->id,
                'to_store_id' => $toStore->id,
                'from_warehouse_id' => $meta['from_warehouse_id'] ?? null,
                'to_warehouse_id' => $meta['to_warehouse_id'] ?? null,
                'transfer_no' => $number,
                'transfer_number' => $number,
                'transfer_type' => $transferType,
                'priority' => $priority,
                'reason' => $meta['reason'] ?? null,
                'reference_no' => $meta['reference_no'] ?? null,
                'notes' => $notes ?? ($meta['notes'] ?? null),
                'status' => 'received',
                'transfer_date' => $transferDate,
                'requested_date' => $meta['requested_date'] ?? $transferDate,
                'expected_date' => $meta['expected_date'] ?? null,
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => $now,
                'released_by' => $user->id,
                'released_at' => $now,
                'received_by' => $user->id,
                'received_at' => $now,
                'transferred_at' => $now,
                'total_items' => 0,
                'total_quantity' => '0',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $seenProducts = [];
            $totalQuantity = '0';

            foreach ($lines as $index => $line) {
                $productId = $line['product_id'];
                $quantity = (string) ($line['requested_quantity'] ?? $line['quantity'] ?? '0');

                if (isset($seenProducts[$productId])) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.product_id" => 'Duplicate product on transfer lines.',
                    ]);
                }
                $seenProducts[$productId] = true;

                if (bccomp($quantity, '0', 4) !== 1) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Quantity must be greater than zero.',
                    ]);
                }

                $product = Product::query()->with(['barcodes' => fn ($q) => $q->orderByDesc('is_primary')])->findOrFail($productId);

                if ($product->company_id !== $fromStore->company_id) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.product_id" => 'Product does not belong to the company.',
                    ]);
                }

                if (! $product->track_inventory) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.product_id" => 'Product does not track inventory.',
                    ]);
                }

                $available = (string) (StoreProductInventory::query()
                    ->where('store_id', $fromStore->id)
                    ->where('product_id', $product->id)
                    ->value('qty') ?? '0');

                if (bccomp($available, $quantity, 4) === -1) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => "Insufficient stock for {$product->name} (available {$available}).",
                    ]);
                }

                $unitCost = (string) ($product->cost ?? '0');
                $barcode = $product->barcodes->first()?->barcode;

                $transfer->lines()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'line_number' => $index + 1,
                    'sku' => $product->sku,
                    'barcode' => $barcode,
                    'product_name' => $product->name,
                    'unit_id' => $product->unit_id,
                    'quantity' => $quantity,
                    'requested_quantity' => $quantity,
                    'approved_quantity' => $quantity,
                    'released_quantity' => $quantity,
                    'received_quantity' => $quantity,
                    'rejected_quantity' => '0',
                    'damaged_quantity' => '0',
                    'unit_cost' => $unitCost,
                    'total_cost' => bcmul($unitCost, $quantity, 4),
                    'batch_no' => $line['batch_no'] ?? null,
                    'serial_no' => $line['serial_no'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'notes' => $line['notes'] ?? null,
                    'status' => 'received',
                ]);

                $this->stockMovement->record(
                    $fromStore->company_id,
                    $fromStore->id,
                    $product->id,
                    'transfer_out',
                    bcmul($quantity, '-1', 4),
                    $user,
                    [
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'reason' => "Transfer {$transfer->transfer_no} to {$toStore->store_code}",
                        'notes' => $transfer->notes,
                    ],
                );

                $this->stockMovement->record(
                    $toStore->company_id,
                    $toStore->id,
                    $product->id,
                    'transfer_in',
                    $quantity,
                    $user,
                    [
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'reason' => "Transfer {$transfer->transfer_no} from {$fromStore->store_code}",
                        'notes' => $transfer->notes,
                    ],
                );

                $totalQuantity = bcadd($totalQuantity, $quantity, 4);
            }

            $transfer->update([
                'total_items' => count($seenProducts),
                'total_quantity' => $totalQuantity,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->log('create', 'inventory', StockTransfer::class, $transfer->id, null, [
                'transfer_no' => $transfer->transfer_no,
                'transfer_type' => $transfer->transfer_type,
                'from_store_id' => $fromStore->id,
                'to_store_id' => $toStore->id,
                'line_count' => count($lines),
            ], $user);

            return $transfer->fresh(['lines.product', 'fromStore', 'toStore', 'fromWarehouse', 'toWarehouse']);
        });
    }

    protected function nextTransferNumber(Store $store): string
    {
        $count = StockTransfer::query()
            ->where('company_id', $store->company_id)
            ->whereDate('created_at', today())
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('TRF-%s-%s-%s', $store->store_code, now()->format('Ymd'), $seq);
    }
}
