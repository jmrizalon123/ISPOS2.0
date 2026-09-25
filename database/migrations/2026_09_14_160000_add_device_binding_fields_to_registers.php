<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (! Schema::hasColumn('registers', 'device_serial')) {
                $table->string('device_serial', 100)->nullable()->after('permit_number');
            }
            if (! Schema::hasColumn('registers', 'reset_registration')) {
                $table->boolean('reset_registration')->default(false)->after('device_serial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            foreach (['device_serial', 'reset_registration'] as $column) {
                if (Schema::hasColumn('registers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
