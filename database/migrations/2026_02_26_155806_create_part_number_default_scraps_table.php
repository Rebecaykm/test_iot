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
        Schema::create('part_number_default_scraps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_number_id')->constrained('part_numbers');
            $table->foreignId('scrap_id')->constrained('scraps');
            $table->decimal('quantity');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_number_default_scraps');
    }
};
