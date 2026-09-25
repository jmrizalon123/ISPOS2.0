<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('from_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUlid('to_store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('transfer_number', 50);
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transfer_number']);
            $table->index(['from_store_id', 'transferred_at']);
            $table->index(['to_store_id', 'transferred_at']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number')->default(1);
            $table->decimal('quantity', 19, 4);
            $table->timestamps();

            $table->unique(['stock_transfer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
