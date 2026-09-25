<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->date('receipt_date');
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'receipt_number']);
            $table->index(['company_id', 'receipt_date']);
            $table->index(['purchase_order_id']);
        });

        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 19, 4);
            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bill_number', 50);
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->decimal('amount_due', 19, 4)->default(0);
            $table->decimal('amount_paid', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'bill_number']);
            $table->index(['company_id', 'status']);
            $table->index(['supplier_id', 'bill_date']);
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number', 50);
            $table->date('payment_date');
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 19, 4);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'payment_number']);
            $table->index(['company_id', 'payment_date']);
            $table->index(['supplier_id']);
        });

        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 19, 4);
            $table->timestamps();

            $table->unique(['supplier_payment_id', 'vendor_bill_id'], 'spa_payment_bill_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('vendor_bills');
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
    }
};
