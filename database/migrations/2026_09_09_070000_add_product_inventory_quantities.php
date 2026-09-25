<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('qty', 19, 4)->default(0)->after('track_inventory');
            $table->decimal('ideal_qty', 19, 4)->nullable()->after('qty');
            $table->decimal('warning_qty', 19, 4)->nullable()->after('ideal_qty');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['qty', 'ideal_qty', 'warning_qty']);
        });
    }
};
