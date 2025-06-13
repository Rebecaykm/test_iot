<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar el campo como nullable temporalmente
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('email');
        });

        // 2. Asignar valores únicos a registros existentes
        DB::statement("
            WITH UpdatedUsers AS (
                SELECT
                    id,
                    LEFT(SUBSTRING(email, 1, CHARINDEX('@', email) - 1), 50) AS user_part,
                    ROW_NUMBER() OVER (PARTITION BY SUBSTRING(email, 1, CHARINDEX('@', email) - 1) ORDER BY id) AS rn
                FROM users
            )
            UPDATE users
            SET nickname =
                CASE
                    WHEN UpdatedUsers.rn = 1 THEN UpdatedUsers.user_part
                    ELSE CONCAT(UpdatedUsers.user_part, '_', UpdatedUsers.rn - 1)
                END
            FROM users
            INNER JOIN UpdatedUsers ON users.id = UpdatedUsers.id
        ");

        // 3. Modificar el campo para que sea único y no nulo
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nickname']);
            $table->dropColumn('nickname');
        });
    }
};
