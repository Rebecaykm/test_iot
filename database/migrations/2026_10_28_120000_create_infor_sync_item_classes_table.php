<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clases de número de parte que se envían a Infor por ambiente. Sin clases
     * seleccionadas el ambiente no envía nada.
     */
    const INITIAL_ITEM_CLASSES = ['M1', 'M2', 'M3', 'M4'];

    public function up(): void
    {
        Schema::create('infor_sync_item_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infor_sync_setting_id')->constrained('infor_sync_settings')->cascadeOnDelete();
            $table->foreignId('item_class_id')->constrained('item_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['infor_sync_setting_id', 'item_class_id']);
        });

        // Los ambientes que ya existen siguen enviando M1-M4, como hasta ahora
        $now = now();
        $itemClassIds = DB::table('item_classes')->whereIn('abbreviation', self::INITIAL_ITEM_CLASSES)->pluck('id');

        foreach (DB::table('infor_sync_settings')->pluck('id') as $settingId) {
            DB::table('infor_sync_item_classes')->insert(
                $itemClassIds->map(fn ($itemClassId) => [
                    'infor_sync_setting_id' => $settingId,
                    'item_class_id' => $itemClassId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('infor_sync_item_classes');
    }
};
