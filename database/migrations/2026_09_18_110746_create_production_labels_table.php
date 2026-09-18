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
        Schema::create('production_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_record_id');
            $table->unsignedBigInteger('product_id');

            $table->integer('global_sequence');
            $table->smallInteger('label_sequence');
            $table->integer('production_order');

            $table->decimal('label_quantity', 11, 3);
            $table->decimal('produced_quantity', 11, 3)->default(0.000);
            $table->decimal('starting_quantity', 11, 3);
            $table->decimal('ending_quantity', 11, 3);
            $table->integer('standard_pack_quantity');

            $table->boolean('is_produced')->default(false);
            $table->dateTime('expected_completion_at')->nullable();
            $table->timestamps();

            $table->foreign('production_record_id')
                ->references('id')
                ->on('production_records');

            $table->foreign('product_id')
                ->references('id')
                ->on('part_numbers');

            $table->unique(['production_record_id', 'label_sequence'], 'UQ_labels_record_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_labels');
    }
};
