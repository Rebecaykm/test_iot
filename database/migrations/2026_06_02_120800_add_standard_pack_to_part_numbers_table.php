<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->foreignId('standard_pack_id')->nullable()->constrained('standard_packs');
            $table->double('standard_pack_quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->dropForeign(['standard_pack_id']);
            $table->dropColumn(['standard_pack_id', 'standard_pack_quantity']);
        });
    }
};
