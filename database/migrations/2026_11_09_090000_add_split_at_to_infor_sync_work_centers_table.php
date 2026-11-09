<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuándo se dividió el acumulado de la estación al arrancar su envío a Infor
     * (lo anterior a sync_from pasa a "No verificado"). Las estaciones que ya
     * enviaban antes de hoy no se dividen.
     */
    public function up(): void
    {
        Schema::table('infor_sync_work_centers', function (Blueprint $table) {
            $table->timestamp('split_at')->nullable();
        });

        DB::table('infor_sync_work_centers')
            ->whereDate('sync_from', '<', now()->toDateString())
            ->update(['split_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('infor_sync_work_centers', function (Blueprint $table) {
            $table->dropColumn('split_at');
        });
    }
};
