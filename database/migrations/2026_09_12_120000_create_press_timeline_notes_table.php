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
        Schema::create('press_timeline_notes', function (Blueprint $table) {
            $table->id();
            $table->string('work_center');
            $table->string('mdi');
            $table->foreignId('shift_id')->constrained('shifts');
            $table->date('date');
            $table->text('body');
            $table->timestamps();

            $table->index(['work_center', 'mdi', 'shift_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('press_timeline_notes');
    }
};
