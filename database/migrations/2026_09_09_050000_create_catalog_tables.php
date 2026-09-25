<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('category_code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'category_code']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('brand_code', 50);
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'brand_code']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('unit_code', 50);
            $table->string('name');
            $table->string('symbol', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'unit_code']);
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('tax_code', 50);
            $table->string('name');
            $table->decimal('rate', 5, 2)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'tax_code']);
        });

        Schema::create('price_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('group_code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'group_code']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUlid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost', 19, 4)->default(0);
            $table->decimal('base_price', 19, 4)->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('has_variants')->default(false);
            $table->string('image')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('variant_code', 50);
            $table->string('name');
            $table->string('sku', 100)->nullable();
            $table->decimal('cost', 19, 4)->default(0);
            $table->decimal('selling_price', 19, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'variant_code']);
        });

        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('barcode', 100);
            $table->boolean('is_primary')->default(false);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'barcode']);
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('price_group_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('price', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(['price_group_id', 'product_id', 'product_variant_id'], 'product_prices_unique');
        });

        if (Schema::hasColumn('stores', 'price_group_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreign('price_group_id')->references('id')->on('price_groups')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'price_group_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropForeign(['price_group_id']);
            });
        }

        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('price_groups');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
