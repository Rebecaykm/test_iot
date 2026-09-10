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
        Schema::create('part_number_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_part_number_id')->constrained('part_numbers');
            $table->foreignId('child_part_number_id')->constrained('part_numbers');
            $table->unsignedInteger('sequence_order')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('discontinue_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_part_number_id', 'child_part_number_id'], 'part_number_relations_unique');
            $table->index(['parent_part_number_id', 'is_active']);
            $table->index(['child_part_number_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_number_relations');
    }
};
