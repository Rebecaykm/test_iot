<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('histories', function (Blueprint $table) {
            $table->index(['part_number_id', 'created_at'], 'histories_part_number_created_at_idx');
            $table->index('created_at', 'histories_created_at_idx');
        });

        Schema::table('work_centers', function (Blueprint $table) {
            $table->index('name', 'work_centers_name_idx');
        });

        Schema::table('part_numbers', function (Blueprint $table) {
            $table->index('work_center_id', 'part_numbers_work_center_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('histories', function (Blueprint $table) {
            $table->dropIndex('histories_part_number_created_at_idx');
            $table->dropIndex('histories_created_at_idx');
        });

        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropIndex('work_centers_name_idx');
        });

        Schema::table('part_numbers', function (Blueprint $table) {
            $table->dropIndex('part_numbers_work_center_id_idx');
        });
    }
};
