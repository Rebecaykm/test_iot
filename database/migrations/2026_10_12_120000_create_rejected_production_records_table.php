<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registros de producción rechazados: lo que el recolector leyó del PLC y no pudo
     * registrar. El recolector inserta el JSON completo en `payload`; el reporte lo
     * separa en columnas al descargarlo, así que un campo nuevo en el JSON no
     * requiere cambiar la tabla.
     */
    public function up(): void
    {
        Schema::create('rejected_production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_center_id')->nullable()->constrained('work_centers');
            $table->text('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rejected_production_records');
    }
};
