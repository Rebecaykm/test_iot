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
        Schema::create('production_label_progress', function (Blueprint $table) {
            $table->foreignId('production_record_id')
                ->primary()
                ->constrained('production_records')
                ->cascadeOnDelete();

            $table->integer('last_produced_quantity')->default(0);
            $table->dateTime('created_at', 3);
            $table->dateTime('updated_at', 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_label_progress');
    }
};
