<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stores', 'code')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->renameColumn('code', 'store_code');
            });
        }

        if (Schema::hasColumn('stores', 'name')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->renameColumn('name', 'store_name');
            });
        }

        if (Schema::hasColumn('stores', 'address') && ! Schema::hasColumn('stores', 'address_line_1')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('address_line_1')->nullable()->after('phone');
            });

            DB::table('stores')->whereNotNull('address')->update([
                'address_line_1' => DB::raw('address'),
            ]);

            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }

        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('stores', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('store_name');
            }
            if (! Schema::hasColumn('stores', 'store_type')) {
                $table->string('store_type', 50)->nullable()->after('legal_name');
            }
            if (! Schema::hasColumn('stores', 'store_category')) {
                $table->string('store_category', 50)->nullable()->after('store_type');
            }
            if (! Schema::hasColumn('stores', 'description')) {
                $table->text('description')->nullable()->after('store_category');
            }
            if (! Schema::hasColumn('stores', 'branch_code')) {
                $table->string('branch_code', 50)->nullable()->after('description');
            }
            if (! Schema::hasColumn('stores', 'tin')) {
                $table->string('tin', 30)->nullable()->after('branch_code');
            }
            if (! Schema::hasColumn('stores', 'bir_registration_no')) {
                $table->string('bir_registration_no', 50)->nullable()->after('tin');
            }
            if (! Schema::hasColumn('stores', 'business_permit_no')) {
                $table->string('business_permit_no', 50)->nullable()->after('bir_registration_no');
            }
            if (! Schema::hasColumn('stores', 'email')) {
                $table->string('email')->nullable()->after('business_permit_no');
            }
            if (! Schema::hasColumn('stores', 'mobile')) {
                $table->string('mobile', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('stores', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('stores', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('stores', 'barangay')) {
                $table->string('barangay', 100)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('stores', 'city')) {
                $table->string('city', 100)->nullable()->after('barangay');
            }
            if (! Schema::hasColumn('stores', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('stores', 'region')) {
                $table->string('region', 100)->nullable()->after('province');
            }
            if (! Schema::hasColumn('stores', 'country')) {
                $table->string('country', 2)->default('PH')->after('region');
            }
            if (! Schema::hasColumn('stores', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('country');
            }
            if (! Schema::hasColumn('stores', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('stores', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('stores', 'manager_id')) {
                $table->foreignUlid('manager_id')->nullable()->after('longitude')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('stores', 'warehouse_id')) {
                $table->ulid('warehouse_id')->nullable()->after('manager_id');
            }
            if (! Schema::hasColumn('stores', 'price_group_id')) {
                $table->ulid('price_group_id')->nullable()->after('warehouse_id');
            }
            if (! Schema::hasColumn('stores', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 5, 2)->nullable()->after('price_group_id');
            }
            if (! Schema::hasColumn('stores', 'currency')) {
                $table->string('currency', 3)->default('PHP')->after('default_tax_rate');
            }
            if (! Schema::hasColumn('stores', 'timezone')) {
                $table->string('timezone', 64)->default('Asia/Manila')->after('currency');
            }
            if (! Schema::hasColumn('stores', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('stores', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
            if (! Schema::hasColumn('stores', 'operating_days')) {
                $table->json('operating_days')->nullable()->after('closing_time');
            }
            if (! Schema::hasColumn('stores', 'is_24_hours')) {
                $table->boolean('is_24_hours')->default(false)->after('operating_days');
            }
            if (! Schema::hasColumn('stores', 'enable_pos')) {
                $table->boolean('enable_pos')->default(true)->after('is_24_hours');
            }
            if (! Schema::hasColumn('stores', 'enable_inventory')) {
                $table->boolean('enable_inventory')->default(true)->after('enable_pos');
            }
            if (! Schema::hasColumn('stores', 'enable_online_ordering')) {
                $table->boolean('enable_online_ordering')->default(false)->after('enable_inventory');
            }
            if (! Schema::hasColumn('stores', 'enable_delivery')) {
                $table->boolean('enable_delivery')->default(false)->after('enable_online_ordering');
            }
            if (! Schema::hasColumn('stores', 'enable_pickup')) {
                $table->boolean('enable_pickup')->default(false)->after('enable_delivery');
            }
            if (! Schema::hasColumn('stores', 'enable_dine_in')) {
                $table->boolean('enable_dine_in')->default(false)->after('enable_pickup');
            }
            if (! Schema::hasColumn('stores', 'enable_takeaway')) {
                $table->boolean('enable_takeaway')->default(false)->after('enable_dine_in');
            }
            if (! Schema::hasColumn('stores', 'receipt_header')) {
                $table->text('receipt_header')->nullable()->after('enable_takeaway');
            }
            if (! Schema::hasColumn('stores', 'receipt_footer')) {
                $table->text('receipt_footer')->nullable()->after('receipt_header');
            }
            if (! Schema::hasColumn('stores', 'logo')) {
                $table->string('logo')->nullable()->after('receipt_footer');
            }
            if (! Schema::hasColumn('stores', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (! Schema::hasColumn('stores', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('stores', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('opened_at');
            }
            if (! Schema::hasColumn('stores', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('stores', 'updated_by')) {
                $table->foreignUlid('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });

        Store::withTrashed()->whereNull('uuid')->each(function (Store $store): void {
            $store->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'updated_by')) {
                $table->dropForeign(['updated_by']);
            }
            if (Schema::hasColumn('stores', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
            if (Schema::hasColumn('stores', 'manager_id')) {
                $table->dropForeign(['manager_id']);
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            $columns = [
                'uuid', 'legal_name', 'store_type', 'store_category', 'description', 'branch_code',
                'tin', 'bir_registration_no', 'business_permit_no', 'email', 'mobile',
                'address_line_1', 'address_line_2', 'barangay', 'city', 'province', 'region', 'country', 'postal_code',
                'latitude', 'longitude', 'manager_id', 'warehouse_id', 'price_group_id', 'default_tax_rate',
                'currency', 'timezone', 'opening_time', 'closing_time', 'operating_days', 'is_24_hours',
                'enable_pos', 'enable_inventory', 'enable_online_ordering', 'enable_delivery', 'enable_pickup',
                'enable_dine_in', 'enable_takeaway', 'receipt_header', 'receipt_footer', 'logo',
                'is_active', 'opened_at', 'closed_at', 'created_by', 'updated_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (! Schema::hasColumn('stores', 'address') && Schema::hasColumn('stores', 'address_line_1')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('address')->nullable()->after('phone');
            });

            DB::table('stores')->update([
                'address' => DB::raw('address_line_1'),
            ]);
        }

        if (Schema::hasColumn('stores', 'store_name')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->renameColumn('store_name', 'name');
            });
        }

        if (Schema::hasColumn('stores', 'store_code')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->renameColumn('store_code', 'code');
            });
        }
    }
};
