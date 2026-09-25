<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('registers', 'register_number')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->dropColumn('register_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('registers', 'register_number')) {
            Schema::table('registers', function (Blueprint $table) {
                $table->string('register_number', 50)->nullable()->after('register_name');
            });
        }
    }
};
