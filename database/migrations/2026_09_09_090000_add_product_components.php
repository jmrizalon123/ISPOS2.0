<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_components')->default(false)->after('has_modifiers');
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 19, 4)->default(1);
            $table->foreignUlid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'component_product_id'], 'product_components_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_components');
        });
    }
};
