<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_product_inventories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 19, 4)->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'product_id']);
            $table->index(['company_id', 'product_id']);
            $table->index(['store_id', 'qty']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type', 40);
            $table->decimal('quantity_delta', 19, 4);
            $table->decimal('qty_before', 19, 4);
            $table->decimal('qty_after', 19, 4);
            $table->foreignUlid('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('sale_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->ulid('reference_id')->nullable();
            $table->foreignUlid('reversal_of_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['store_id', 'product_id', 'created_at']);
            $table->index(['sale_id']);
            $table->index(['movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('store_product_inventories');
    }
};
