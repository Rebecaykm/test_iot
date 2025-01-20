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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('address')->nullable();
            $table->integer('long')->nullable();
            $table->string('value')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('work_center_id')->nullable()->constrained('work_centers');
            $table->foreignId('tag_type_id')->nullable()->constrained('tag_types');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
