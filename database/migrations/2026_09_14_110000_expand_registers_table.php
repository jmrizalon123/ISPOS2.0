<?php

use App\Models\Register;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('registers', 'code')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->renameColumn('code', 'register_code');
            });
        }

        if (Schema::hasColumn('registers', 'name')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->renameColumn('name', 'register_name');
            });
        }

        Schema::table('registers', function (Blueprint $table) {
            if (! Schema::hasColumn('registers', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('registers', 'company_id')) {
                $table->foreignUlid('company_id')->nullable()->after('uuid')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('registers', 'register_number')) {
                $table->string('register_number', 50)->nullable()->after('register_name');
            }
            if (! Schema::hasColumn('registers', 'terminal_code')) {
                $table->string('terminal_code', 50)->nullable()->after('register_number');
            }
            if (! Schema::hasColumn('registers', 'terminal_name')) {
                $table->string('terminal_name')->nullable()->after('terminal_code');
            }
            if (! Schema::hasColumn('registers', 'device_id')) {
                $table->foreignUlid('device_id')->nullable()->after('terminal_name')->constrained('pos_devices')->nullOnDelete();
            }
            if (! Schema::hasColumn('registers', 'device_name')) {
                $table->string('device_name')->nullable()->after('device_id');
            }
            if (! Schema::hasColumn('registers', 'device_type')) {
                $table->string('device_type', 50)->nullable()->after('device_name');
            }
            if (! Schema::hasColumn('registers', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('device_type');
            }
            if (! Schema::hasColumn('registers', 'mac_address')) {
                $table->string('mac_address', 17)->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('registers', 'printer_id')) {
                $table->string('printer_id', 26)->nullable()->after('mac_address');
            }
            if (! Schema::hasColumn('registers', 'cash_drawer_id')) {
                $table->string('cash_drawer_id', 26)->nullable()->after('printer_id');
            }
            if (! Schema::hasColumn('registers', 'customer_display_id')) {
                $table->string('customer_display_id', 26)->nullable()->after('cash_drawer_id');
            }
            if (! Schema::hasColumn('registers', 'kds_station_id')) {
                $table->string('kds_station_id', 26)->nullable()->after('customer_display_id');
            }
            if (! Schema::hasColumn('registers', 'default_warehouse_id')) {
                $table->ulid('default_warehouse_id')->nullable()->after('kds_station_id');
            }
            if (! Schema::hasColumn('registers', 'default_price_group_id')) {
                $table->foreignUlid('default_price_group_id')->nullable()->after('default_warehouse_id')->constrained('price_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('registers', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 8, 4)->nullable()->after('default_price_group_id');
            }
            if (! Schema::hasColumn('registers', 'currency')) {
                $table->string('currency', 3)->default('PHP')->after('default_tax_rate');
            }
            if (! Schema::hasColumn('registers', 'receipt_printer_name')) {
                $table->string('receipt_printer_name')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('registers', 'receipt_printer_type')) {
                $table->string('receipt_printer_type', 50)->nullable()->after('receipt_printer_name');
            }
            if (! Schema::hasColumn('registers', 'receipt_printer_ip')) {
                $table->string('receipt_printer_ip', 45)->nullable()->after('receipt_printer_type');
            }
            if (! Schema::hasColumn('registers', 'receipt_printer_port')) {
                $table->unsignedSmallInteger('receipt_printer_port')->nullable()->after('receipt_printer_ip');
            }
            if (! Schema::hasColumn('registers', 'drawer_open_method')) {
                $table->string('drawer_open_method', 50)->nullable()->after('receipt_printer_port');
            }

            $booleanColumns = [
                'allow_cash_sales' => true,
                'allow_card_sales' => true,
                'allow_gcash_sales' => false,
                'allow_maya_sales' => false,
                'allow_other_payments' => true,
                'allow_discount' => true,
                'allow_void' => false,
                'allow_refund' => false,
                'allow_reprint' => true,
                'allow_price_override' => false,
                'allow_open_drawer' => false,
                'require_cashier_login' => true,
                'require_manager_approval' => false,
                'auto_print_receipt' => true,
                'auto_print_kitchen_order' => false,
                'auto_print_customer_receipt' => false,
                'enable_customer_display' => false,
                'enable_kds' => false,
                'enable_ncs' => false,
                'online_order_enabled' => false,
                'offline_mode_enabled' => true,
                'sync_enabled' => true,
                'is_active' => true,
            ];

            foreach ($booleanColumns as $column => $default) {
                if (! Schema::hasColumn('registers', $column)) {
                    $table->boolean($column)->default($default);
                }
            }

            if (! Schema::hasColumn('registers', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable();
            }
            if (! Schema::hasColumn('registers', 'last_z_read_at')) {
                $table->timestamp('last_z_read_at')->nullable();
            }
            if (! Schema::hasColumn('registers', 'current_shift_id')) {
                $table->foreignUlid('current_shift_id')->nullable()->constrained('pos_shifts')->nullOnDelete();
            }
            if (! Schema::hasColumn('registers', 'current_cashier_id')) {
                $table->foreignUlid('current_cashier_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('registers', 'opened_at')) {
                $table->timestamp('opened_at')->nullable();
            }
            if (! Schema::hasColumn('registers', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
        });

        if (Schema::hasColumn('registers', 'company_id')) {
            DB::table('registers')
                ->whereNull('company_id')
                ->update([
                    'company_id' => DB::raw('(SELECT company_id FROM stores WHERE stores.id = registers.store_id LIMIT 1)'),
                ]);
        }

        Register::withTrashed()->whereNull('uuid')->each(function (Register $register): void {
            $register->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            foreach (['current_cashier_id', 'current_shift_id', 'default_price_group_id', 'device_id', 'company_id'] as $foreign) {
                if (Schema::hasColumn('registers', $foreign)) {
                    $table->dropForeign([$foreign]);
                }
            }
        });

        Schema::table('registers', function (Blueprint $table) {
            $columns = [
                'uuid', 'company_id', 'register_number', 'terminal_code', 'terminal_name', 'device_id',
                'device_name', 'device_type', 'ip_address', 'mac_address', 'printer_id', 'cash_drawer_id',
                'customer_display_id', 'kds_station_id', 'default_warehouse_id', 'default_price_group_id',
                'default_tax_rate', 'currency', 'receipt_printer_name', 'receipt_printer_type',
                'receipt_printer_ip', 'receipt_printer_port', 'drawer_open_method', 'allow_cash_sales',
                'allow_card_sales', 'allow_gcash_sales', 'allow_maya_sales', 'allow_other_payments',
                'allow_discount', 'allow_void', 'allow_refund', 'allow_reprint', 'allow_price_override',
                'allow_open_drawer', 'require_cashier_login', 'require_manager_approval', 'auto_print_receipt',
                'auto_print_kitchen_order', 'auto_print_customer_receipt', 'enable_customer_display',
                'enable_kds', 'enable_ncs', 'online_order_enabled', 'offline_mode_enabled', 'sync_enabled',
                'last_sync_at', 'last_z_read_at', 'current_shift_id', 'current_cashier_id', 'is_active',
                'opened_at', 'closed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('registers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('registers', 'register_name')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->renameColumn('register_name', 'name');
            });
        }

        if (Schema::hasColumn('registers', 'register_code')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->renameColumn('register_code', 'code');
            });
        }
    }
};
