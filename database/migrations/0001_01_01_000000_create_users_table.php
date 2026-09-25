<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->string('company_code', 50)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('company_type', 50)->nullable();
            $table->string('industry', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('tin', 30)->nullable();
            $table->string('bir_registration_no', 50)->nullable();
            $table->string('sec_registration_no', 50)->nullable();
            $table->string('dti_registration_no', 50)->nullable();
            $table->string('business_permit_no', 50)->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->string('taxpayer_type', 50)->nullable();
            $table->decimal('default_tax_rate', 5, 2)->default(12);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 2)->default('PH');
            $table->string('postal_code', 20)->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('base_currency', 3)->default('PHP');
            $table->string('currency_symbol', 10)->default('₱');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->unsignedTinyInteger('fiscal_year_start_day')->default(1);
            $table->string('accounting_method', 30)->default('accrual');
            $table->unsignedSmallInteger('default_payment_terms_days')->default(30);
            $table->string('timezone', 64)->default('Asia/Manila');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 20)->default('H:i');
            $table->string('language', 10)->default('en');
            $table->boolean('enable_pos')->default(true);
            $table->boolean('enable_inventory')->default(true);
            $table->boolean('enable_accounting')->default(false);
            $table->boolean('enable_hr')->default(false);
            $table->boolean('enable_crm')->default(false);
            $table->boolean('enable_ecommerce')->default(false);
            $table->ulid('subscription_plan_id')->nullable();
            $table->timestamp('subscription_start_at')->nullable();
            $table->timestamp('subscription_end_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('store_code', 50);
            $table->string('store_name');
            $table->string('legal_name')->nullable();
            $table->string('store_type', 50)->nullable();
            $table->string('store_category', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('branch_code', 50)->nullable();
            $table->string('tin', 30)->nullable();
            $table->string('bir_registration_no', 50)->nullable();
            $table->string('business_permit_no', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 2)->default('PH');
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->ulid('warehouse_id')->nullable();
            $table->ulid('price_group_id')->nullable();
            $table->decimal('default_tax_rate', 5, 2)->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->string('timezone', 64)->default('Asia/Manila');
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_days')->nullable();
            $table->boolean('is_24_hours')->default(false);
            $table->boolean('enable_pos')->default(true);
            $table->boolean('enable_inventory')->default(true);
            $table->boolean('enable_online_ordering')->default(false);
            $table->boolean('enable_delivery')->default(false);
            $table->boolean('enable_pickup')->default(false);
            $table->boolean('enable_dine_in')->default(false);
            $table->boolean('enable_takeaway')->default(false);
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'store_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('registers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'code']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('preferred_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        Schema::create('store_user', function (Blueprint $table) {
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['store_id', 'user_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUlid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('registers');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('companies');
    }
};
