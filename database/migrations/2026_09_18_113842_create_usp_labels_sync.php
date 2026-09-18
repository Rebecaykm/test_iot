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
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.labels_sync;');
            $path = database_path('sql/procedures/labels_sync.sql');

            if (file_exists($path)) {
                DB::unprepared(file_get_contents($path));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::unprepared('DROP PROCEDURE IF EXISTS dbo.labels_sync;');
        }
    }
};
