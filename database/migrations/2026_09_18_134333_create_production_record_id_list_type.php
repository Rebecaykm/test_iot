<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.get_label_tracking_by_workcenter;');
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.get_label_tracking;');

            DB::statement('
                CREATE TYPE [dbo].[ProductionRecordIdList] AS TABLE (
                    [ProductionRecordId] BIGINT NOT NULL,
                    PRIMARY KEY CLUSTERED ([ProductionRecordId] ASC)
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.get_label_tracking_by_workcenter;');
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.get_label_tracking;');
            DB::statement('DROP TYPE IF EXISTS [dbo].[ProductionRecordIdList];');
        }
    }
};
