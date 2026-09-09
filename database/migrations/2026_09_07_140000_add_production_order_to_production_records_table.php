<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->integer('production_order')->nullable()->after('shop_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropColumn('production_order');
        });
    }
};
