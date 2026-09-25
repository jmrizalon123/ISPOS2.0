<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'department_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferred_store_id')) {
                $table->renameColumn('preferred_store_id', 'default_store_id');
            }

            if (! Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 100)->nullable()->unique()->after('employee_id');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('password_changed_at');
            }

            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            }

            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }

            if (! Schema::hasColumn('users', 'suffix')) {
                $table->string('suffix', 20)->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name')->nullable()->after('suffix');
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('display_name');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignUlid('department_id')->nullable()->after('avatar')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'position_id')) {
                $table->foreignUlid('position_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'default_warehouse_id')) {
                $table->foreignUlid('default_warehouse_id')->nullable()->after('default_store_id')->constrained('stores')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'base_type')) {
                $table->string('base_type', 20)->default('store')->after('default_warehouse_id');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }

            if (! Schema::hasColumn('users', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('is_locked');
            }

            if (! Schema::hasColumn('users', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('failed_login_attempts');
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_login_ip');
            }

            if (! Schema::hasColumn('users', 'language')) {
                $table->string('language', 10)->default('en')->after('last_activity_at');
            }

            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 64)->default('Asia/Manila')->after('language');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'base_type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'timezone',
                'language',
                'last_activity_at',
                'last_login_ip',
                'locked_at',
                'failed_login_attempts',
                'is_locked',
                'is_active',
                'base_type',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'default_warehouse_id')) {
                $table->dropConstrainedForeignId('default_warehouse_id');
            }

            if (Schema::hasColumn('users', 'position_id')) {
                $table->dropConstrainedForeignId('position_id');
            }

            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }

            foreach (['avatar', 'display_name', 'suffix', 'last_name', 'middle_name', 'first_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            foreach (['two_factor_confirmed_at', 'two_factor_secret', 'two_factor_enabled', 'password_changed_at', 'phone', 'username', 'employee_id', 'uuid'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'default_store_id')) {
                $table->renameColumn('default_store_id', 'preferred_store_id');
            }
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
