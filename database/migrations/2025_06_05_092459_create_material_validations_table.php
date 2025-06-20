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
        Schema::create('material_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('work_center_id')->nullable()->constrained('work_centers');
            $table->string('container_code')->nullable();
            $table->string('visual_aid_code')->nullable();
            $table->string('final_label_code')->nullable();
            $table->string('part_number')->nullable();
            $table->enum('validation_status', ['OK', 'NG']);
            $table->string('validation_comment')->nullable();
            $table->json('validation_details')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'work_center_id']);
            $table->index(['validation_status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_validations');
    }
};
