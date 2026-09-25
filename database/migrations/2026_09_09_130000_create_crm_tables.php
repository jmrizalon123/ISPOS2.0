<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('program_code', 50);
            $table->string('name');
            $table->decimal('earn_rate', 19, 4)->default(1);
            $table->decimal('redeem_value_per_point', 19, 4)->default(0);
            $table->decimal('min_redeem_points', 19, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'program_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('plan_code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->foreignUlid('price_group_id')->nullable()->constrained('price_groups')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'plan_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('customer_code', 50);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->foreignUlid('price_group_id')->nullable()->constrained('price_groups')->nullOnDelete();
            $table->foreignUlid('loyalty_program_id')->nullable()->constrained('loyalty_programs')->nullOnDelete();
            $table->decimal('loyalty_points', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'customer_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'email']);
            $table->index(['company_id', 'phone']);
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('loyalty_program_id')->nullable()->constrained('loyalty_programs')->nullOnDelete();
            $table->string('transaction_type', 30);
            $table->decimal('points_delta', 19, 4);
            $table->decimal('balance_after', 19, 4);
            $table->string('reference_type')->nullable();
            $table->ulid('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('customer_memberships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['membership_plan_id', 'status']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('promo_code', 50);
            $table->string('name');
            $table->string('promo_type', 30);
            $table->decimal('discount_value', 19, 4);
            $table->decimal('min_purchase_amount', 19, 4)->nullable();
            $table->string('applies_to', 30)->default('all');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'promo_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'starts_at', 'ends_at']);
        });

        Schema::create('promotion_product', function (Blueprint $table) {
            $table->foreignUlid('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::create('promotion_category', function (Blueprint $table) {
            $table->foreignUlid('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['promotion_id', 'category_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUlid('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignUlid('promotion_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->decimal('loyalty_points_earned', 19, 4)->default(0)->after('discount_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn('loyalty_points_earned');
        });

        Schema::dropIfExists('promotion_category');
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('customer_memberships');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('loyalty_programs');
    }
};
