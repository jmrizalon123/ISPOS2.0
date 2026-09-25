<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('voided_by');
            $table->foreignUlid('refunded_by')->nullable()->after('refunded_at')->constrained('users')->nullOnDelete();
            $table->string('refund_reason', 255)->nullable()->after('refunded_by');
        });

        Schema::create('sale_refunds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('pos_shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 19, 4);
            $table->string('payment_method', 40)->default('cash');
            $table->string('reason', 255);
            $table->timestamp('refunded_at');
            $table->timestamps();

            $table->index(['pos_shift_id', 'refunded_at']);
            $table->index(['store_id', 'refunded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refunds');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['refunded_at', 'refund_reason']);
        });
    }
};
