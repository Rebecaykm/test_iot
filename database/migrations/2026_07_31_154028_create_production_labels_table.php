<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_record_id');
            $table->foreignId('completed_history_id')
                ->nullable()
                ->constrained('histories')
                ->nullOnDelete();
            $table->integer('production_sequence');
            $table->integer('label_number');
            $table->string('label_code', 12); // / Shop order (8D) + '-' (1D) + sequence (3D) = 12D (example: 12345678-001)
            $table->integer('snp');
            $table->integer('quantity_planned');
            $table->integer('quantity_completed');
            $table->integer('range_start');
            $table->integer('range_end');
            $table->dateTime('expected_completion_at', 3);
            $table->dateTime('completed_at', 3)->nullable();
            $table->timestamps(3);

            $table->foreign('production_record_id')
                ->references('id')
                ->on('production_records');

            $table->unique('label_code');
        });

        DB::statement('
            ALTER TABLE production_labels
            ADD is_completed AS
            (
                CASE
                    WHEN quantity_planned = quantity_completed
                    THEN CAST(1 AS BIT)
                    ELSE CAST(0 AS BIT)
                END
            ) PERSISTED
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_labels');
    }
};
