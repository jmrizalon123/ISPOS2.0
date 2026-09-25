<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('register_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->decimal('opening_float', 19, 4)->default(0);
            $table->decimal('closing_float', 19, 4)->nullable();
            $table->decimal('expected_cash', 19, 4)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['register_id', 'status']);
            $table->index(['store_id', 'opened_at']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->string('sale_number', 50);
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('register_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('pos_shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('completed');
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignUlid('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'sale_number']);
            $table->index(['store_id', 'completed_at']);
            $table->index(['pos_shift_id', 'status']);
        });

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedSmallInteger('line_number')->default(1);
            $table->string('sku', 100)->nullable();
            $table->string('name');
            $table->decimal('qty', 19, 4)->default(1);
            $table->decimal('unit_price', 19, 4)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_line_modifiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_line_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_modifier_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('product_modifier_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('modifier_group_name');
            $table->string('option_name');
            $table->decimal('price_adjustment', 19, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_line_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_line_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('component_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('component_name');
            $table->decimal('quantity', 19, 4)->default(1);
            $table->boolean('is_optional')->default(false);
            $table->boolean('included')->default(true);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 19, 4);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_line_components');
        Schema::dropIfExists('sale_line_modifiers');
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('pos_shifts');
    }
};
