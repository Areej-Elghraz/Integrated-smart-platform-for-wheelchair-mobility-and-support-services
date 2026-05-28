<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            // Drop old generic columns if they exist
            $colsToDrop = [];
            foreach (['sensor_type', 'value', 'reading_time'] as $c) {
                if (Schema::hasColumn('sensor_readings', $c)) {
                    $colsToDrop[] = $c;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });

        Schema::table('sensor_readings', function (Blueprint $table) {
            // Add trip_id reference
            if (!Schema::hasColumn('sensor_readings', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->constrained('trips')->cascadeOnDelete()->after('wheelchair_id');
            }

            // Heart rate per-reading aggregated
            if (!Schema::hasColumn('sensor_readings', 'heart_rate_min')) {
                $table->double('heart_rate_min')->nullable();
                $table->double('heart_rate_max')->nullable();
                $table->double('heart_rate_avg')->nullable();
            }

            // Temperature per-reading aggregated
            if (!Schema::hasColumn('sensor_readings', 'temperature_min')) {
                $table->double('temperature_min')->nullable();
                $table->double('temperature_max')->nullable();
                $table->double('temperature_avg')->nullable();
            }

            // MPU angle per-reading aggregated
            if (!Schema::hasColumn('sensor_readings', 'mpu_angle_min')) {
                $table->double('mpu_angle_min')->nullable();
                $table->double('mpu_angle_max')->nullable();
                $table->double('mpu_angle_avg')->nullable();
            }

            // Reading time for this data window
            if (!Schema::hasColumn('sensor_readings', 'reading_time')) {
                $table->timestamp('reading_time')->nullable();
            }
        });

        Schema::table('sensor_readings', function (Blueprint $table) {
            // check if index exists
            $indexes = Schema::getIndexes('sensor_readings');
            $indexNames = array_column($indexes, 'name');
            if (!in_array('sensor_readings_wheelchair_id_reading_time_index', $indexNames)) {
                $table->index(['wheelchair_id', 'reading_time']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropColumn([
                'trip_id',
                'heart_rate_min', 'heart_rate_max', 'heart_rate_avg',
                'temperature_min', 'temperature_max', 'temperature_avg',
                'mpu_angle_min', 'mpu_angle_max', 'mpu_angle_avg',
                'reading_time'
            ]);

            // Restore original columns
            $table->enum('sensor_type', ['mpu', 'heart', 'temperature']);
            $table->json('value');
            $table->timestamp('reading_time')->nullable();
        });
    }
};
