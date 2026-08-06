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
            $table->index('part_number_id', 'production_records_part_number_id_idx');
            $table->index('shift_id', 'production_records_shift_id_idx');
            $table->index('status_id', 'production_records_status_id_idx');
            $table->index(['planned_date', 'shift_id'], 'production_records_planned_date_shift_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropIndex('production_records_part_number_id_idx');
            $table->dropIndex('production_records_shift_id_idx');
            $table->dropIndex('production_records_status_id_idx');
            $table->dropIndex('production_records_planned_date_shift_id_idx');
        });
    }
};
