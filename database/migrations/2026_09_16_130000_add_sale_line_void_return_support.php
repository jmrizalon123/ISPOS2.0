<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_lines')) {
            return;
        }

        Schema::table('sale_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_lines', 'line_status')) {
                $table->string('line_status', 20)->default('active')->after('line_total');
                $table->timestamp('voided_at')->nullable()->after('line_status');
                $table->timestamp('returned_at')->nullable()->after('voided_at');
                $table->index(['sale_id', 'line_status']);
            }
        });

        if (Schema::hasTable('sale_refunds') && ! Schema::hasTable('sale_refund_lines')) {
            Schema::table('sale_refunds', function (Blueprint $table) {
                $table->dropForeign(['sale_id']);
            });

            Schema::table('sale_refunds', function (Blueprint $table) {
                $table->dropUnique(['sale_id']);
                $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
                $table->index('sale_id');
            });

            Schema::create('sale_refund_lines', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('sale_refund_id')->constrained('sale_refunds')->cascadeOnDelete();
                $table->foreignUlid('sale_line_id')->constrained('sale_lines')->cascadeOnDelete();
                $table->decimal('qty', 19, 4);
                $table->decimal('amount', 19, 4);
                $table->timestamps();

                $table->unique(['sale_refund_id', 'sale_line_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refund_lines');

        if (Schema::hasTable('sale_refunds')) {
            Schema::table('sale_refunds', function (Blueprint $table) {
                $table->dropForeign(['sale_id']);
                $table->dropIndex(['sale_id']);
            });

            Schema::table('sale_refunds', function (Blueprint $table) {
                $table->unique('sale_id');
                $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('sale_lines')) {
            Schema::table('sale_lines', function (Blueprint $table) {
                if (Schema::hasColumn('sale_lines', 'line_status')) {
                    $table->dropIndex(['sale_id', 'line_status']);
                    $table->dropColumn(['line_status', 'voided_at', 'returned_at']);
                }
            });
        }
    }
};
