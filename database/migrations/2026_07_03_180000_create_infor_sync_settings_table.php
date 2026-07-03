<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('infor_sync_settings', function (Blueprint $table) {
            $table->id();
            $table->string('environment')->unique();
            $table->boolean('enabled')->default(false);
            $table->json('work_center_ids')->nullable();
            $table->timestamps();
        });

        // Valores iniciales que replican el comportamiento actual de los jobs:
        // Live sincroniza las estaciones de la línea Miniceldas y Proto la estación 2500T TR (111010)
        $miniceldasIds = DB::table('work_centers')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->where('lines.name', 'Miniceldas')
            ->pluck('work_centers.id')
            ->toArray();

        $protoIds = DB::table('work_centers')
            ->where('number', '111010')
            ->pluck('id')
            ->toArray();

        $now = now();

        DB::table('infor_sync_settings')->insert([
            [
                'environment' => 'live',
                'enabled' => true,
                'work_center_ids' => json_encode($miniceldasIds),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'environment' => 'proto',
                'enabled' => true,
                'work_center_ids' => json_encode($protoIds),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infor_sync_settings');
    }
};
