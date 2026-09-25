<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $translatableColumns = [
        'categories' => ['name', 'description'],
        'brands' => ['name'],
        'units' => ['name'],
        'taxes' => ['name'],
        'price_groups' => ['name', 'description'],
        'products' => ['name', 'description'],
        'product_variants' => ['name'],
        'product_modifier_groups' => ['name'],
        'product_modifier_options' => ['name'],
        'promotions' => ['name'],
        'membership_plans' => ['name', 'description'],
        'loyalty_programs' => ['name'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $default = 'en';

        foreach ($this->translatableColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $tempColumn = "{$column}_revert_tmp";
                $nullable = $column === 'description';
                $type = $column === 'description' ? 'TEXT' : 'VARCHAR(255)';
                $nullSql = $nullable ? 'NULL' : 'NULL';

                DB::statement("ALTER TABLE `{$table}` ADD `{$tempColumn}` {$type} {$nullSql}");
                DB::statement(
                    "UPDATE `{$table}` SET `{$tempColumn}` = JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.\"{$default}\"')) WHERE `{$column}` IS NOT NULL AND JSON_VALID(`{$column}`)"
                );
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
                DB::statement("ALTER TABLE `{$table}` CHANGE `{$tempColumn}` `{$column}` {$type} ".($nullable ? 'NULL' : 'NOT NULL'));
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty — translatable support was removed.
    }
};
