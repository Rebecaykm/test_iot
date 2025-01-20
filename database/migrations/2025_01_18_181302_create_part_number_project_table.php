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
        Schema::create('part_number_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_number_id')->constrained('part_numbers');
            $table->foreignId('project_id')->constrained('projects');
            $table->timestamps();

            $table->unique(['part_number_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_number_project');
    }
};
