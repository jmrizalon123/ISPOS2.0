<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesPlan;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_plans')) {
            Schema::create('sales_plans', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->string('plan_code', 50);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'plan_code']);
            });
        }

        if (Schema::hasTable('stores') && ! Schema::hasColumn('stores', 'sales_plan_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreignUlid('sales_plan_id')->nullable()->after('price_group_id')->constrained('sales_plans')->nullOnDelete();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'sales_plan_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignUlid('sales_plan_id')->nullable()->after('company_id')->constrained('sales_plans')->nullOnDelete();
            });
        }

        $this->backfillDefaultPlans();
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sales_plan_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sales_plan_id');
            });
        }

        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'sales_plan_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sales_plan_id');
            });
        }

        Schema::dropIfExists('sales_plans');
    }

    protected function backfillDefaultPlans(): void
    {
        if (! Schema::hasTable('sales_plans') || ! Schema::hasTable('companies')) {
            return;
        }

        Company::query()->each(function (Company $company): void {
            $plan = SalesPlan::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'plan_code' => 'DEFAULT',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Default',
                    'description' => 'Starter sales plan. Split retail and restaurant catalogs by creating additional plans.',
                    'status' => 'active',
                ],
            );

            Store::query()
                ->where('company_id', $company->id)
                ->whereNull('sales_plan_id')
                ->update(['sales_plan_id' => $plan->id]);

            Product::query()
                ->where('company_id', $company->id)
                ->whereNull('sales_plan_id')
                ->update(['sales_plan_id' => $plan->id]);
        });
    }
};
