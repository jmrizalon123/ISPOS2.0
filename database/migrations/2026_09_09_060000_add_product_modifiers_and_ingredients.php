<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 20)->default('retail')->after('description');
            $table->boolean('has_modifiers')->default(false)->after('has_variants');
        });

        Schema::create('product_modifier_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('group_code', 50);
            $table->string('name');
            $table->string('selection_type', 20)->default('single');
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('min_selections')->default(0);
            $table->unsignedSmallInteger('max_selections')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'group_code']);
        });

        Schema::create('product_modifier_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('product_modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('option_code', 50);
            $table->string('name');
            $table->decimal('price_adjustment', 19, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_modifier_group_id', 'option_code'], 'product_modifier_options_unique');
        });

        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('ingredient_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 19, 4)->default(1);
            $table->foreignUlid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'ingredient_product_id'], 'product_ingredients_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
        Schema::dropIfExists('product_modifier_options');
        Schema::dropIfExists('product_modifier_groups');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'has_modifiers']);
        });
    }
};
