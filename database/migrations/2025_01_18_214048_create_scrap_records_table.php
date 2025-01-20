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
        Schema::create('scrap_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_number_id')->nullable()->constrained('part_numbers');
            $table->foreignId('scrap_id')->nullable()->constrained('scraps');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrap_records');
    }
};
