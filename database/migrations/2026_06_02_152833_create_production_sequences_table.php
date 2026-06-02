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
        Schema::create('production_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_record_id')->nullable()->constrained('production_records');
            $table->integer('sequence_number');
            $table->integer('quantity');
            $table->boolean('is_processed')->default(false);
            $table->text('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_sequences');
    }
};
