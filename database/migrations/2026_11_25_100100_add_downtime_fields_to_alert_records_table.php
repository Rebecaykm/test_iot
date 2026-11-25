<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada falla del PLC con su inicio, fin y turno. La tabla está vacía, por eso
     * las columnas NOT NULL no necesitan valor por defecto.
     */
    public function up(): void
    {
        Schema::table('alert_records', function (Blueprint $table) {
            $table->dropForeign('alert_records_failure_id_foreign');
            $table->dropColumn('failure_id');
            $table->dateTime('start_time', 0);
            $table->dateTime('end_time', 0)->nullable();
            $table->foreignId('shift_id')->constrained('shifts');
            $table->date('shift_date');

            $table->index(['work_center_id', 'start_time'], 'alert_records_work_center_id_start_time_index');
        });

        DB::statement('CREATE UNIQUE INDEX alert_records_alert_id_abierta_unique ON alert_records (alert_id) WHERE end_time IS NULL AND alert_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX alert_records_alert_id_abierta_unique ON alert_records');

        Schema::table('alert_records', function (Blueprint $table) {
            $table->dropIndex('alert_records_work_center_id_start_time_index');
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['start_time', 'end_time', 'shift_id', 'shift_date']);

            $table->foreignId('failure_id')->nullable()->constrained('failures');
        });
    }
};
