<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings_aggregated', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->cascadeOnDelete();

            // Heart rate aggregated values
            $table->double('heart_rate_min')->nullable();
            $table->double('heart_rate_max')->nullable();
            $table->double('heart_rate_avg')->nullable();

            // Temperature aggregated values
            $table->double('temperature_min')->nullable();
            $table->double('temperature_max')->nullable();
            $table->double('temperature_avg')->nullable();

            // MPU angle aggregated values
            $table->double('mpu_angle_min')->nullable();
            $table->double('mpu_angle_max')->nullable();
            $table->double('mpu_angle_avg')->nullable();

            // Time window this aggregation covers
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();

            $table->timestamps();

            $table->index(['wheelchair_id', 'window_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings_aggregated');
    }
};
