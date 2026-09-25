<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->renameColumn('code', 'company_code');
            });
        }

        if (Schema::hasColumn('companies', 'currency') && ! Schema::hasColumn('companies', 'base_currency')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('base_currency', 3)->default('PHP')->after('timezone');
            });

            DB::table('companies')->update([
                'base_currency' => DB::raw('currency'),
            ]);

            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('companies', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('companies', 'display_name')) {
                $table->string('display_name')->nullable()->after('legal_name');
            }
            if (! Schema::hasColumn('companies', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('companies', 'company_type')) {
                $table->string('company_type', 50)->nullable()->after('trade_name');
            }
            if (! Schema::hasColumn('companies', 'industry')) {
                $table->string('industry', 100)->nullable()->after('company_type');
            }
            if (! Schema::hasColumn('companies', 'description')) {
                $table->text('description')->nullable()->after('industry');
            }

            if (! Schema::hasColumn('companies', 'tin')) {
                $table->string('tin', 30)->nullable()->after('description');
            }
            if (! Schema::hasColumn('companies', 'bir_registration_no')) {
                $table->string('bir_registration_no', 50)->nullable()->after('tin');
            }
            if (! Schema::hasColumn('companies', 'sec_registration_no')) {
                $table->string('sec_registration_no', 50)->nullable()->after('bir_registration_no');
            }
            if (! Schema::hasColumn('companies', 'dti_registration_no')) {
                $table->string('dti_registration_no', 50)->nullable()->after('sec_registration_no');
            }
            if (! Schema::hasColumn('companies', 'business_permit_no')) {
                $table->string('business_permit_no', 50)->nullable()->after('dti_registration_no');
            }
            if (! Schema::hasColumn('companies', 'vat_registered')) {
                $table->boolean('vat_registered')->default(false)->after('business_permit_no');
            }
            if (! Schema::hasColumn('companies', 'taxpayer_type')) {
                $table->string('taxpayer_type', 50)->nullable()->after('vat_registered');
            }
            if (! Schema::hasColumn('companies', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 5, 2)->default(12)->after('taxpayer_type');
            }

            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('default_tax_rate');
            }
            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('companies', 'mobile')) {
                $table->string('mobile', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('mobile');
            }

            if (! Schema::hasColumn('companies', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('website');
            }
            if (! Schema::hasColumn('companies', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('companies', 'barangay')) {
                $table->string('barangay', 100)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city', 100)->nullable()->after('barangay');
            }
            if (! Schema::hasColumn('companies', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('companies', 'region')) {
                $table->string('region', 100)->nullable()->after('province');
            }
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 2)->default('PH')->after('region');
            }
            if (! Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('country');
            }

            if (! Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('companies', 'favicon')) {
                $table->string('favicon')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('companies', 'primary_color')) {
                $table->string('primary_color', 7)->nullable()->after('favicon');
            }
            if (! Schema::hasColumn('companies', 'secondary_color')) {
                $table->string('secondary_color', 7)->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('companies', 'receipt_header')) {
                $table->text('receipt_header')->nullable()->after('secondary_color');
            }
            if (! Schema::hasColumn('companies', 'receipt_footer')) {
                $table->text('receipt_footer')->nullable()->after('receipt_header');
            }

            if (! Schema::hasColumn('companies', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->default('₱')->after('base_currency');
            }
            if (! Schema::hasColumn('companies', 'fiscal_year_start_month')) {
                $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('currency_symbol');
            }
            if (! Schema::hasColumn('companies', 'fiscal_year_start_day')) {
                $table->unsignedTinyInteger('fiscal_year_start_day')->default(1)->after('fiscal_year_start_month');
            }
            if (! Schema::hasColumn('companies', 'accounting_method')) {
                $table->string('accounting_method', 30)->default('accrual')->after('fiscal_year_start_day');
            }
            if (! Schema::hasColumn('companies', 'default_payment_terms_days')) {
                $table->unsignedSmallInteger('default_payment_terms_days')->default(30)->after('accounting_method');
            }

            if (! Schema::hasColumn('companies', 'date_format')) {
                $table->string('date_format', 20)->default('Y-m-d')->after('timezone');
            }
            if (! Schema::hasColumn('companies', 'time_format')) {
                $table->string('time_format', 20)->default('H:i')->after('date_format');
            }
            if (! Schema::hasColumn('companies', 'language')) {
                $table->string('language', 10)->default('en')->after('time_format');
            }

            if (! Schema::hasColumn('companies', 'enable_pos')) {
                $table->boolean('enable_pos')->default(true)->after('language');
            }
            if (! Schema::hasColumn('companies', 'enable_inventory')) {
                $table->boolean('enable_inventory')->default(true)->after('enable_pos');
            }
            if (! Schema::hasColumn('companies', 'enable_accounting')) {
                $table->boolean('enable_accounting')->default(false)->after('enable_inventory');
            }
            if (! Schema::hasColumn('companies', 'enable_hr')) {
                $table->boolean('enable_hr')->default(false)->after('enable_accounting');
            }
            if (! Schema::hasColumn('companies', 'enable_crm')) {
                $table->boolean('enable_crm')->default(false)->after('enable_hr');
            }
            if (! Schema::hasColumn('companies', 'enable_ecommerce')) {
                $table->boolean('enable_ecommerce')->default(false)->after('enable_crm');
            }

            if (! Schema::hasColumn('companies', 'subscription_plan_id')) {
                $table->ulid('subscription_plan_id')->nullable()->after('enable_ecommerce');
            }
            if (! Schema::hasColumn('companies', 'subscription_start_at')) {
                $table->timestamp('subscription_start_at')->nullable()->after('subscription_plan_id');
            }
            if (! Schema::hasColumn('companies', 'subscription_end_at')) {
                $table->timestamp('subscription_end_at')->nullable()->after('subscription_start_at');
            }
            if (! Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_end_at');
            }

            if (! Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }

            if (! Schema::hasColumn('companies', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('companies', 'updated_by')) {
                $table->foreignUlid('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });

        Company::withTrashed()->whereNull('uuid')->each(function (Company $company): void {
            $company->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'updated_by')) {
                $table->dropForeign(['updated_by']);
            }
            if (Schema::hasColumn('companies', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            $columns = [
                'uuid', 'legal_name', 'display_name', 'trade_name', 'company_type', 'industry', 'description',
                'tin', 'bir_registration_no', 'sec_registration_no', 'dti_registration_no', 'business_permit_no',
                'vat_registered', 'taxpayer_type', 'default_tax_rate', 'email', 'phone', 'mobile', 'website',
                'address_line_1', 'address_line_2', 'barangay', 'city', 'province', 'region', 'country', 'postal_code',
                'logo', 'favicon', 'primary_color', 'secondary_color', 'receipt_header', 'receipt_footer',
                'currency_symbol', 'fiscal_year_start_month', 'fiscal_year_start_day', 'accounting_method',
                'default_payment_terms_days', 'date_format', 'time_format', 'language',
                'enable_pos', 'enable_inventory', 'enable_accounting', 'enable_hr', 'enable_crm', 'enable_ecommerce',
                'subscription_plan_id', 'subscription_start_at', 'subscription_end_at', 'trial_ends_at',
                'is_active', 'created_by', 'updated_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('companies', 'base_currency') && ! Schema::hasColumn('companies', 'currency')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('currency', 3)->default('PHP')->after('timezone');
            });

            DB::table('companies')->update([
                'currency' => DB::raw('base_currency'),
            ]);

            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('base_currency');
            });
        }

        if (Schema::hasColumn('companies', 'company_code') && ! Schema::hasColumn('companies', 'code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->renameColumn('company_code', 'code');
            });
        }
    }
};
