<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_user', function (Blueprint $table) {
            if (Schema::hasColumn('store_user', 'assignment_type')) {
                $table->dropColumn('assignment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_user', function (Blueprint $table) {
            $table->string('assignment_type', 20)->default('user')->after('user_id');
        });
    }
};
