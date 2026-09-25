<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('register_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('fingerprint', 191)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->timestamp('last_bootstrap_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['register_id', 'status']);
            $table->unique(['company_id', 'fingerprint']);
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pos_device_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('status', 20);
            $table->unsignedInteger('records_count')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['pos_device_id', 'created_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUlid('pos_device_id')->nullable()->after('register_id')->constrained('pos_devices')->nullOnDelete();
            $table->string('sync_source', 20)->default('pos')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_device_id');
            $table->dropColumn('sync_source');
        });

        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('pos_devices');
    }
};
