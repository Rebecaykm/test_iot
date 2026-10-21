<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha de inicio de envío a Infor por estación y ambiente. Reemplaza el arreglo
     * JSON infor_sync_settings.work_center_ids: los jobs solo envían registros con
     * planned_date >= sync_from, así una estación nueva no arrastra semanas anteriores.
     */
    const INITIAL_SYNC_FROM = '2026-09-21';

    public function up(): void
    {
        Schema::create('infor_sync_work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infor_sync_setting_id')->constrained('infor_sync_settings')->cascadeOnDelete();
            $table->foreignId('work_center_id')->constrained('work_centers')->cascadeOnDelete();
            $table->date('sync_from');
            $table->timestamps();

            $table->unique(['infor_sync_setting_id', 'work_center_id']);
        });

        // Las estaciones que ya se enviaban conservan su comportamiento desde el 21 de septiembre
        $now = now();

        foreach (DB::table('infor_sync_settings')->get() as $setting) {
            $workCenterIds = collect(json_decode($setting->work_center_ids ?? '[]', true))
                ->map(fn ($id) => (int) $id)
                ->unique();

            $existingIds = DB::table('work_centers')->whereIn('id', $workCenterIds)->pluck('id');

            DB::table('infor_sync_work_centers')->insert(
                $existingIds->map(fn ($workCenterId) => [
                    'infor_sync_setting_id' => $setting->id,
                    'work_center_id' => $workCenterId,
                    'sync_from' => self::INITIAL_SYNC_FROM,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }

        // work_center_ids se conserva (sin uso) para no romper código anterior que apunte
        // a esta misma base mientras se despliega; se elimina en una migración posterior.
    }

    public function down(): void
    {
        foreach (DB::table('infor_sync_settings')->get() as $setting) {
            $workCenterIds = DB::table('infor_sync_work_centers')
                ->where('infor_sync_setting_id', $setting->id)
                ->pluck('work_center_id')
                ->map(fn ($id) => (string) $id)
                ->values();

            DB::table('infor_sync_settings')
                ->where('id', $setting->id)
                ->update(['work_center_ids' => json_encode($workCenterIds)]);
        }

        Schema::dropIfExists('infor_sync_work_centers');
    }
};
