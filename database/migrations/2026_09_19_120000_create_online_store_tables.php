<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_store_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->boolean('is_published')->default(false);
            $table->string('storefront_name')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->boolean('accept_pickup')->default(true);
            $table->boolean('accept_delivery')->default(false);
            $table->boolean('accept_dine_in')->default(false);
            $table->decimal('min_order_amount', 19, 4)->default(0);
            $table->decimal('delivery_fee', 19, 4)->default(0);
            $table->decimal('free_delivery_threshold', 19, 4)->nullable();
            $table->unsignedSmallInteger('preparation_minutes')->default(30);
            $table->json('payment_methods')->nullable();
            $table->boolean('auto_accept_orders')->default(false);
            $table->text('announcement')->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->string('support_email')->nullable();
            $table->time('orders_open_at')->nullable();
            $table->time('orders_close_at')->nullable();
            $table->json('orders_open_days')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_published']);
        });

        Schema::create('online_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->string('order_number', 50);
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 50);
            $table->string('fulfillment_type', 20)->default('pickup');
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 30)->default('cod');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('delivery_address_line_1')->nullable();
            $table->string('delivery_address_line_2')->nullable();
            $table->string('delivery_barangay')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_province')->nullable();
            $table->string('delivery_postal_code', 20)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('delivery_fee', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->foreignUlid('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUlid('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->string('source', 30)->default('web');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'order_number']);
            $table->index(['store_id', 'status', 'created_at']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('online_order_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('online_order_id')->constrained('online_orders')->cascadeOnDelete();
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

        Schema::create('online_order_line_modifiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('online_order_line_id')->constrained('online_order_lines')->cascadeOnDelete();
            $table->foreignUlid('product_modifier_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('product_modifier_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('modifier_group_name');
            $table->string('option_name');
            $table->decimal('price_adjustment', 19, 4)->default(0);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'source')) {
                $table->string('source', 30)->default('pos')->after('status');
            }
            if (! Schema::hasColumn('sales', 'order_type')) {
                $table->string('order_type', 30)->nullable()->after('source');
            }
            if (! Schema::hasColumn('sales', 'online_order_id')) {
                $table->foreignUlid('online_order_id')->nullable()->after('order_type')->constrained('online_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'online_order_id')) {
                $table->dropConstrainedForeignId('online_order_id');
            }
            if (Schema::hasColumn('sales', 'order_type')) {
                $table->dropColumn('order_type');
            }
            if (Schema::hasColumn('sales', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::dropIfExists('online_order_line_modifiers');
        Schema::dropIfExists('online_order_lines');
        Schema::dropIfExists('online_orders');
        Schema::dropIfExists('online_store_settings');
    }
};
