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
            $table->string('shop_order_number')->nullable()->after('status_id');
            $table->boolean('synced_to_infor')->default(false)->after('shop_order_number');
            $table->timestamp('synced_at')->nullable()->after('synced_to_infor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropColumn(['shop_order_number', 'synced_to_infor', 'synced_at']);
        });
    }
};
