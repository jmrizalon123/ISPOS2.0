<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (! Schema::hasColumn('registers', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->after('register_name');
            }
            if (! Schema::hasColumn('registers', 'min')) {
                $table->string('min', 100)->nullable()->after('serial_number');
            }
            if (! Schema::hasColumn('registers', 'permit_number')) {
                $table->string('permit_number', 100)->nullable()->after('min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            foreach (['serial_number', 'min', 'permit_number'] as $column) {
                if (Schema::hasColumn('registers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
