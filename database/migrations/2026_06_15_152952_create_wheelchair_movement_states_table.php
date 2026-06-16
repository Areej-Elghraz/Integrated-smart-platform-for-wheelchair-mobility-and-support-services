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
        Schema::dropIfExists('trip_movement_states');

        Schema::create('wheelchair_movement_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->string('movement_status');
            $table->double('speed');
            $table->json('position');
            $table->double('theta');
            $table->string('mode');
            $table->string('risk_level');
            $table->boolean('obstacle_detected');
            $table->double('obstacle_distance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheelchair_movement_states');
    }
};
