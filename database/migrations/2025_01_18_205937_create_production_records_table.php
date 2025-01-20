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
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_number_id')->constrained('part_numbers');
            $table->integer('planned_quantity')->default(0);
            $table->integer('produced_quantity')->default(0);
            $table->integer('scrap_quantity')->default(0);
            $table->date('planned_date');
            $table->timestamp('production_start');
            $table->timestamp('production_end');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_records');
    }
};
