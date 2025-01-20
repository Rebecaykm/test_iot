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
        Schema::create('alert_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_center_id')->nullable()->constrained('work_centers');
            $table->foreignId('alert_id')->nullable()->constrained('alerts');
            $table->foreignId('failure_id')->nullable()->constrained('failures');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_records');
    }
};
