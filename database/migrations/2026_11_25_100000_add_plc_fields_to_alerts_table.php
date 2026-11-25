<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de bits del PLC por estación. La tabla está vacía, por eso las
     * columnas NOT NULL no necesitan valor por defecto.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('work_center_id')->constrained('work_centers');
            $table->string('bit_device', 10);
            $table->string('word_device', 10);
            $table->unsignedTinyInteger('bit_index');
            $table->string('stop_category', 20)->nullable();

            $table->unique(['work_center_id', 'bit_device'], 'alerts_work_center_id_bit_device_unique');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropUnique('alerts_work_center_id_bit_device_unique');
            $table->dropForeign(['work_center_id']);
            $table->dropColumn(['work_center_id', 'bit_device', 'word_device', 'bit_index', 'stop_category']);
        });
    }
};
