<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transfers', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (! Schema::hasColumn('stock_transfers', 'transfer_no')) {
                $table->string('transfer_no', 50)->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('stock_transfers', 'from_warehouse_id')) {
                $table->ulid('from_warehouse_id')->nullable()->after('to_store_id');
            }
            if (! Schema::hasColumn('stock_transfers', 'to_warehouse_id')) {
                $table->ulid('to_warehouse_id')->nullable()->after('from_warehouse_id');
            }
            if (! Schema::hasColumn('stock_transfers', 'transfer_date')) {
                $table->date('transfer_date')->nullable()->after('to_warehouse_id');
            }
            if (! Schema::hasColumn('stock_transfers', 'requested_date')) {
                $table->date('requested_date')->nullable()->after('transfer_date');
            }
            if (! Schema::hasColumn('stock_transfers', 'expected_date')) {
                $table->date('expected_date')->nullable()->after('requested_date');
            }
            if (! Schema::hasColumn('stock_transfers', 'transfer_type')) {
                $table->string('transfer_type', 40)->default('store_to_store')->after('expected_date');
            }
            if (! Schema::hasColumn('stock_transfers', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('transfer_type');
            }
            if (! Schema::hasColumn('stock_transfers', 'reason')) {
                $table->string('reason', 255)->nullable()->after('priority');
            }
            if (! Schema::hasColumn('stock_transfers', 'reference_no')) {
                $table->string('reference_no', 80)->nullable()->after('reason');
            }
            if (! Schema::hasColumn('stock_transfers', 'requested_by')) {
                $table->ulid('requested_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('stock_transfers', 'approved_by')) {
                $table->ulid('approved_by')->nullable()->after('requested_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'released_by')) {
                $table->ulid('released_by')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('stock_transfers', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('released_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'received_by')) {
                $table->ulid('received_by')->nullable()->after('released_at');
            }
            if (! Schema::hasColumn('stock_transfers', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'cancelled_by')) {
                $table->ulid('cancelled_by')->nullable()->after('received_at');
            }
            if (! Schema::hasColumn('stock_transfers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'cancellation_reason')) {
                $table->string('cancellation_reason', 500)->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('stock_transfers', 'total_items')) {
                $table->unsignedInteger('total_items')->default(0)->after('cancellation_reason');
            }
            if (! Schema::hasColumn('stock_transfers', 'total_quantity')) {
                $table->decimal('total_quantity', 19, 4)->default(0)->after('total_items');
            }
        });

        $this->widenStatusColumn();

        foreach (DB::table('stock_transfers')->orderBy('id')->get() as $row) {
            $updates = [];
            if (empty($row->uuid)) {
                $updates['uuid'] = (string) Str::uuid();
            }
            $number = $row->transfer_no ?? $row->transfer_number ?? null;
            if (empty($row->transfer_no) && $number) {
                $updates['transfer_no'] = $number;
            }
            if (($row->status ?? null) === 'completed') {
                $updates['status'] = 'received';
            }
            $transferredAt = $row->transferred_at ?? null;
            if (empty($row->transfer_date) && $transferredAt) {
                $updates['transfer_date'] = substr((string) $transferredAt, 0, 10);
            }
            if (empty($row->requested_date) && ! empty($updates['transfer_date'] ?? $row->transfer_date ?? null)) {
                $updates['requested_date'] = $updates['transfer_date'] ?? $row->transfer_date;
            }
            if (empty($row->received_at) && $transferredAt) {
                $updates['received_at'] = $transferredAt;
            }
            if ($updates !== []) {
                DB::table('stock_transfers')->where('id', $row->id)->update($updates);
            }
        }

        if (! $this->hasIndexNamed('stock_transfers', 'stock_transfers_company_id_transfer_no_unique')
            && Schema::hasColumn('stock_transfers', 'transfer_no')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->unique(['company_id', 'transfer_no']);
            });
        }

        if (Schema::hasTable('stock_transfer_lines') && ! Schema::hasTable('stock_transfer_items')) {
            Schema::rename('stock_transfer_lines', 'stock_transfer_items');
        }

        if (! Schema::hasTable('stock_transfer_items')) {
            return;
        }

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transfer_items', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'product_variant_id')) {
                $table->ulid('product_variant_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'sku')) {
                $table->string('sku', 80)->nullable()->after('product_variant_id');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'barcode')) {
                $table->string('barcode', 80)->nullable()->after('sku');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'product_name')) {
                $table->string('product_name', 255)->nullable()->after('barcode');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'unit_id')) {
                $table->ulid('unit_id')->nullable()->after('product_name');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'requested_quantity')) {
                $table->decimal('requested_quantity', 19, 4)->nullable()->after('unit_id');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'approved_quantity')) {
                $table->decimal('approved_quantity', 19, 4)->nullable()->after('requested_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'released_quantity')) {
                $table->decimal('released_quantity', 19, 4)->nullable()->after('approved_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'received_quantity')) {
                $table->decimal('received_quantity', 19, 4)->nullable()->after('released_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'rejected_quantity')) {
                $table->decimal('rejected_quantity', 19, 4)->default(0)->after('received_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'damaged_quantity')) {
                $table->decimal('damaged_quantity', 19, 4)->default(0)->after('rejected_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'unit_cost')) {
                $table->decimal('unit_cost', 19, 4)->nullable()->after('damaged_quantity');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'total_cost')) {
                $table->decimal('total_cost', 19, 4)->nullable()->after('unit_cost');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'batch_no')) {
                $table->string('batch_no', 80)->nullable()->after('total_cost');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'serial_no')) {
                $table->string('serial_no', 80)->nullable()->after('batch_no');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('serial_no');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'notes')) {
                $table->string('notes', 500)->nullable()->after('expiry_date');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'status')) {
                $table->string('status', 32)->default('draft')->after('notes');
            }
            if (! Schema::hasColumn('stock_transfer_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $hasQuantity = Schema::hasColumn('stock_transfer_items', 'quantity');
        foreach (DB::table('stock_transfer_items')->orderBy('id')->get() as $row) {
            $updates = [];
            if (empty($row->uuid)) {
                $updates['uuid'] = (string) Str::uuid();
            }
            $qty = $hasQuantity ? ($row->quantity ?? null) : null;
            if ($qty === null) {
                $qty = $row->requested_quantity ?? null;
            }
            if ($qty !== null) {
                if ($row->requested_quantity === null) {
                    $updates['requested_quantity'] = $qty;
                }
                if ($row->approved_quantity === null) {
                    $updates['approved_quantity'] = $qty;
                }
                if ($row->released_quantity === null) {
                    $updates['released_quantity'] = $qty;
                }
                if ($row->received_quantity === null) {
                    $updates['received_quantity'] = $qty;
                }
            }
            if (($row->status ?? null) === 'draft' || empty($row->status)) {
                $updates['status'] = 'received';
            }
            if ($updates !== []) {
                DB::table('stock_transfer_items')->where('id', $row->id)->update($updates);
            }
        }

        $totals = DB::table('stock_transfer_items')
            ->select('stock_transfer_id', DB::raw('count(*) as total_items'), DB::raw('sum(requested_quantity) as total_quantity'))
            ->groupBy('stock_transfer_id')
            ->get();

        foreach ($totals as $total) {
            DB::table('stock_transfers')->where('id', $total->stock_transfer_id)->update([
                'total_items' => (int) $total->total_items,
                'total_quantity' => $total->total_quantity ?? 0,
            ]);
        }
    }

    public function down(): void
    {
        // Forward-only schema expansion.
    }

    protected function widenStatusColumn(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY status VARCHAR(32) NOT NULL DEFAULT 'draft'");
        }
    }

    protected function hasIndexNamed(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? '') === $index);
        }

        if (method_exists(Schema::class, 'hasIndex')) {
            return Schema::hasIndex($table, $index);
        }

        return false;
    }
};
