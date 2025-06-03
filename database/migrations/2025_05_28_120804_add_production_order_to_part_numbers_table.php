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
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->integer('production_order')->nullable()->after('is_obsolete');

            $table->index(['work_center_id', 'production_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->dropIndex(['work_center_id', 'production_order']);
            $table->dropColumn('production_order');
        });
    }
};
